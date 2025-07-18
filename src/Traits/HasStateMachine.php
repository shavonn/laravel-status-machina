<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\Traits;

use SysMatter\StatusMachina\State\StateManager;
use SysMatter\StatusMachina\StatusMachina;

trait HasStateMachine
{
    /** @var array<string, StateManager> Cached state managers */
    protected array $stateMachineCache = [];

    /**
     * Transition to a new state
     */
    public function transitionTo(string $transition, array $context = [], string $property = 'status'): void
    {
        $this->stateMachine($property)->transition($transition, $context);
    }

    /**
     * Get the state machine for the specified property
     */
    public function stateMachine(string $property = 'status'): StateManager
    {
        return $this->stateMachineCache[$property] ??= StatusMachina::for($this, $property);
    }

    /**
     * Get the previous state
     */
    public function previousState(string $property = 'status'): string
    {
        return $this->stateMachine($property)->getPreviousState();
    }

    /**
     * Check if can transition to a state
     */
    public function canTransitionTo(string $state, string $property = 'status'): bool
    {
        return $this->stateMachine($property)->canTransitionTo($state);
    }

    /**
     * Get available transitions from current state
     */
    public function availableTransitions(string $property = 'status'): array
    {
        return $this->stateMachine($property)->getAvailableTransitions();
    }

    /**
     * Check if the model is in a specific state
     */
    public function stateIs(string $state, string $property = 'status'): bool
    {
        return $this->currentState($property) === $state;
    }

    /**
     * Get the current state
     */
    public function currentState(string $property = 'status'): string
    {
        return $this->stateMachine($property)->getState();
    }

    /**
     * Check if the model is in any of the given states
     */
    public function stateIsAny(array $states, string $property = 'status'): bool
    {
        return in_array($this->currentState($property), $states);
    }

    /**
     * Get state metadata
     */
    public function getStateMetadata(string $property = 'status'): array
    {
        return $this->stateMachine($property)->getMetadata();
    }
}
