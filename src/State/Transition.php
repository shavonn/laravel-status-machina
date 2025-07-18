<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\State;

class Transition
{
    protected string|array $from = [];

    protected string $to = '';

    // Simple array property without hooks for guards
    protected array $guards = [];

    protected array $metadata = [];

    public function from(string|array $states): static
    {
        $this->from = $states;

        return $this;
    }

    public function to(string $state): static
    {
        $this->to = $state;

        return $this;
    }

    public function guard(callable $guard): static
    {
        // Remove the redundant is_callable check since $guard is already typed as callable
        $this->guards[] = $guard;

        return $this;
    }

    public function withMetadata(array $metadata): static
    {
        $this->metadata = [...$this->metadata, ...$metadata];

        return $this;
    }

    // Public getters
    public function getFrom(): string|array
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getGuards(): array
    {
        return $this->guards;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function passesGuards(object $model, array $context = []): bool
    {
        return array_all(
            $this->guards,
            fn ($guard) => $guard($model, $context) === true
        );
    }

    /**
     * Check if this transition allows a specific from state
     */
    public function allowsFrom(string $state): bool
    {
        return $this->from === '*' ||
            (is_string($this->from) && $this->from === $state) ||
            (is_array($this->from) && in_array($state, $this->from));
    }

    /**
     * Get a string representation of the transition
     */
    public function __toString(): string
    {
        $from = is_array($this->from) ? implode('|', $this->from) : $this->from;

        return "{$from} → {$this->to}";
    }
}
