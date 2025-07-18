<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\State;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use SysMatter\StatusMachina\Authorization\AuthorizationManager;
use SysMatter\StatusMachina\Contracts\StateConfigInterface;
use SysMatter\StatusMachina\Exceptions\AuthorizationException;
use SysMatter\StatusMachina\Exceptions\InvalidTransitionException;
use SysMatter\StatusMachina\History\StateTransitionRepository;
use SysMatter\StatusMachina\Hooks\HookManager;
use SysMatter\StatusMachina\Hooks\HookType;
use Throwable;

class StateManager
{
    protected readonly AuthorizationManager $authManager;

    protected readonly HookManager $hookManager;

    protected string $currentState {
        get => $this->_currentState;
        set {
            if ($this->_currentState !== '') {
                $this->previousState = $this->_currentState;
                $this->stateHistory[] = [
                    'from' => $this->previousState,
                    'to' => $value,
                    'at' => now()->toIso8601String(),
                ];
            }
            $this->_currentState = $value;
        }
    }

    protected string $previousState = '';

    protected array $stateHistory = [];

    protected readonly ?StateTransitionRepository $historyRepository;

    protected readonly bool $trackHistory;

    private string $_currentState = '';

    /**
     * @throws Throwable
     * @throws BindingResolutionException
     * @throws InvalidTransitionException
     */
    public function __construct(
        protected readonly object $model,
        protected readonly string $property,
        protected readonly StateConfigInterface $config,
        protected readonly Application $app
    ) {
        $this->authManager = new AuthorizationManager($app);
        $this->hookManager = new HookManager($config->hooks());

        // Determine if history tracking is enabled
        $this->trackHistory = $this->shouldTrackHistory();

        // Initialize history repository if tracking is enabled
        $this->historyRepository = $this->trackHistory
            ? $app->make(StateTransitionRepository::class)
            : null;

        // Initialize state
        $this->initializeState();
    }
    public function getConfig(): StateConfigInterface
    {
        return $this->config;
    }
    protected function shouldTrackHistory(): bool
    {
        // Check state config first (takes precedence)
        $configTracking = $this->config->historyTracking();
        if ($configTracking !== null) {
            return $configTracking['enabled'] ?? false;
        }

        // Fall back to package config
        return config('status-machina.db_history_tracking.enabled', false);
    }

    /**
     * @throws Throwable
     * @throws InvalidTransitionException
     */
    protected function initializeState(): void
    {
        // Get current state from model
        $modelState = $this->getModelState();

        // If model is newly instantiated, transition to initial state
        if ($modelState === null || $modelState === '') {
            $this->currentState = 'instantiated';
            $this->transition('init');
        } else {
            $this->currentState = $modelState;
        }
    }

    protected function getModelState(): ?string
    {
        return match (true) {
            method_exists($this->model, 'getAttribute') => $this->model->getAttribute($this->property),
            default => $this->model->{$this->property} ?? null,
        };
    }

    /**
     * @throws Throwable
     * @throws InvalidTransitionException
     */
    public function transition(string $transitionName, array $context = []): void
    {
        $transitions = $this->config->transitions();

        if (!isset($transitions[$transitionName])) {
            throw new InvalidTransitionException(
                message: "Transition '{$transitionName}' does not exist",
                code: 404
            );
        }

        $transition = $transitions[$transitionName];

        if (!$this->isValidTransition($transition, $this->currentState)) {
            throw new InvalidTransitionException(
                message: "Cannot transition '{$transitionName}' from state '{$this->currentState}'",
                code: 400
            );
        }

        // Check authorization BEFORE starting transition
        $this->checkAuthorization($transitionName, Auth::user(), $context);

        $toState = $transition->getTo();
        $fromState = $this->currentState;

        // Execute hooks in order: transition, from, to
        try {
            // Before hooks
            $this->executeHooksInOrder(
                hooks: [
                    HookType::BeforeTransition->buildKey($transitionName),
                    HookType::BeforeStateFrom->buildKey($fromState),
                    HookType::BeforeStateTo->buildKey($toState),
                ],
                context: $context
            );

            // Perform state change
            $this->setModelState($toState);

            // Record history if enabled
            if ($this->trackHistory && $this->historyRepository) {
                $this->historyRepository->record(
                    model: $this->model,
                    property: $this->property,
                    fromState: $fromState,
                    toState: $toState,
                    transition: $transitionName,
                    context: $context,
                    transitioner: Auth::user(),
                    metadata: [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]
                );
            }

            // After hooks
            $this->executeHooksInOrder(
                hooks: [
                    HookType::AfterTransition->buildKey($transitionName),
                    HookType::AfterStateFrom->buildKey($fromState),
                    HookType::AfterStateTo->buildKey($toState),
                ],
                context: $context
            );

        } catch (Throwable $e) {
            // Revert state on failure
            $this->currentState = $fromState;
            throw $e;
        }
    }

    protected function isValidTransition(Transition $transition, string $fromState, ?string $toState = null): bool
    {
        $allowedFrom = $transition->getFrom();

        // Check if transition allows from current state
        if ($allowedFrom !== '*' && ! in_array($fromState, (array) $allowedFrom)) {
            return false;
        }

        // If toState specified, check if it matches
        if ($toState !== null && $transition->getTo() !== $toState) {
            return false;
        }

        return true;
    }

    protected function executeHooksInOrder(array $hooks, array $context): void
    {
        foreach ($hooks as $hookType) {
            $this->hookManager->executeHooks($hookType, $this->model, $context);
        }
    }

    protected function setModelState(string $state): void
    {
        match (true) {
            method_exists($this->model, 'setAttribute') => $this->model->setAttribute($this->property, $state),
            default => $this->model->{$this->property} = $state,
        };

        $this->currentState = $state;
    }

    public function getState(): string
    {
        return $this->currentState;
    }

    public function getPreviousState(): string
    {
        return $this->previousState;
    }

    public function getStateHistory(): array
    {
        return $this->stateHistory;
    }

    public function userCanTransitionTo(string $toState, array $context = [], ?object $user = null): bool
    {
        if (! $this->canTransitionTo($toState)) {
            return false;
        }

        $user ??= Auth::user();
        $transitionName = $this->findTransitionName($this->currentState, $toState);

        if ($transitionName === null) {
            return false;
        }

        try {
            $this->checkAuthorization($transitionName, $user, $context);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    public function canTransitionTo(string $toState): bool
    {
        return array_any(
            $this->config->transitions(),
            fn ($transition) => $this->isValidTransition($transition, $this->currentState, $toState)
        );
    }

    protected function findTransitionName(string $fromState, string $toState): ?string
    {
        return $this->findTransitionByStates($fromState, $toState);
    }

    public function findTransitionByStates(string $from, string $to): ?string
    {
        return array_find_key(
            $this->config->transitions(),
            fn ($transition) => $this->isValidTransition($transition, $from, $to)
        );
    }

    /**
     * @throws AuthorizationException
     */
    protected function checkAuthorization(string $transitionName, ?object $user, array $context): void
    {
        $protectHook = $this->hookManager->getHook(HookType::Protect->buildKey($transitionName));

        if ($protectHook !== null) {
            $ability = $protectHook->getAuthorizationAbility();
            if ($ability !== null) {
                $this->authManager->authorize($ability, $user, $this->model, $context);
            }
        }
    }

    public function hasAnyTransition(array $transitionNames): bool
    {
        return array_any(
            $transitionNames,
            fn ($name) => in_array($name, $this->getAvailableTransitions())
        );
    }

    public function getAvailableTransitions(): array
    {
        $transitions = $this->config->transitions();

        return array_keys(
            array_filter(
                $transitions,
                fn ($transition) => $this->isValidTransition($transition, $this->currentState)
            )
        );
    }

    public function canTransitionToAll(array $states): bool
    {
        return array_all(
            $states,
            fn ($state) => $this->canTransitionTo($state)
        );
    }

    /**
     * Get state metadata (history, transitions, etc.)
     */
    public function getMetadata(): array
    {
        return [
            'current_state' => $this->currentState,
            'previous_state' => $this->previousState,
            'available_transitions' => $this->getAvailableTransitions(),
            'history_count' => count($this->stateHistory),
            'last_transition' => $this->stateHistory[array_key_last($this->stateHistory)] ?? null,
        ];
    }
}
