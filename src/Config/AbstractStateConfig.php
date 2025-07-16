<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Config;

use LogicException;
use Shavonn\StatusMachina\Contracts\StateConfigInterface;
use Shavonn\StatusMachina\Hooks\Hook;
use Shavonn\StatusMachina\Hooks\HookType;
use Shavonn\StatusMachina\State\Transition;

abstract class AbstractStateConfig implements StateConfigInterface
{
    protected string $initialState = '';

    /** @var array<int, string> */
    protected array $states = [];

    /** @var array<string, Transition> */
    protected array $transitions = [];

    /** @var array<string, array<int, Hook>> */
    protected array $hooks = [];

    protected ?array $historyTracking = null;

    // Simple boolean property without hooks
    protected bool $finalized = false;

    public function initialState(): string
    {
        return $this->initialState;
    }

    public function states(): array
    {
        // Always include 'instantiated' state
        return array_unique([...['instantiated'], ...$this->states]);
    }

    public function transitions(): array
    {
        // Always include 'init' transition
        return [
            'init' => $this->transition()
                ->from('instantiated')
                ->to($this->initialState),
            ...$this->transitions,
        ];
    }

    /**
     * Create a new transition builder
     */
    protected function transition(): Transition
    {
        return new Transition();
    }

    public function hooks(): array
    {
        return $this->hooks;
    }

    public function historyTracking(): ?array
    {
        return $this->historyTracking;
    }

    /**
     * Finalize the configuration (no more changes allowed)
     */
    public function finalize(): static
    {
        $this->finalized = true;

        return $this;
    }

    /**
     * Add a state
     */
    protected function state(string $state): static
    {
        $this->ensureNotFinalized();
        $this->states[] = $state;

        return $this;
    }

    protected function ensureNotFinalized(): void
    {
        if ($this->finalized) {
            throw new LogicException('Cannot modify finalized state configuration');
        }
    }

    /**
     * Add multiple states
     */
    protected function addStates(array $states): static
    {
        $this->ensureNotFinalized();
        array_push($this->states, ...$states);

        return $this;
    }

    /**
     * Set a transition
     */
    protected function setTransition(string $name, Transition $transition): static
    {
        $this->ensureNotFinalized();
        $this->transitions[$name] = $transition;

        return $this;
    }

    /**
     * Before transition hook
     */
    protected function beforeTransition(string $transition, callable|string|array $callback): static
    {
        return $this->addHook(
            type: HookType::BeforeTransition->buildKey($transition),
            hook: $this->hook()->callback($callback)
        );
    }

    /**
     * Add a hook
     */
    protected function addHook(string $type, Hook $hook): static
    {
        $this->ensureNotFinalized();
        $this->hooks[$type][] = $hook;

        return $this;
    }

    /**
     * Create a new hook builder
     */
    protected function hook(): Hook
    {
        return new Hook();
    }

    /**
     * After transition hook
     */
    protected function afterTransition(string $transition, callable|string|array $callback): static
    {
        return $this->addHook(
            type: HookType::AfterTransition->buildKey($transition),
            hook: $this->hook()->callback($callback)
        );
    }

    /**
     * Before state change hook
     */
    protected function beforeStateTo(string $state, callable|string|array $callback): static
    {
        return $this->addHook(
            type: HookType::BeforeStateTo->buildKey($state),
            hook: $this->hook()->callback($callback)
        );
    }

    /**
     * After state change hook
     */
    protected function afterStateTo(string $state, callable|string|array $callback): static
    {
        return $this->addHook(
            type: HookType::AfterStateTo->buildKey($state),
            hook: $this->hook()->callback($callback)
        );
    }

    /**
     * Before leaving state hook
     */
    protected function beforeStateFrom(string $state, callable|string|array $callback): static
    {
        return $this->addHook(
            type: HookType::BeforeStateFrom->buildKey($state),
            hook: $this->hook()->callback($callback)
        );
    }

    /**
     * After leaving state hook
     */
    protected function afterStateFrom(string $state, callable|string|array $callback): static
    {
        return $this->addHook(
            type: HookType::AfterStateFrom->buildKey($state),
            hook: $this->hook()->callback($callback)
        );
    }

    /**
     * Protect transition with authorization
     */
    protected function protectTransition(string $transition, string $ability): static
    {
        return $this->addHook(
            type: HookType::Protect->buildKey($transition),
            hook: $this->hook()->authorization($ability)
        );
    }

    /**
     * Configure history tracking
     */
    protected function trackHistory(?string $type = null, array $options = []): static
    {
        $type ??= 'database';
        $this->ensureNotFinalized();
        $this->historyTracking = ['type' => $type, ...$options];

        return $this;
    }
}
