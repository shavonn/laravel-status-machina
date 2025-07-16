<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\State;

use InvalidArgumentException;
use Stringable;

class State implements Stringable
{
    protected string $name {
        get => $this->_name;
        set {
            if ($value === '') {
                throw new InvalidArgumentException('State name cannot be empty');
            }
            $this->_name = $value;
        }
    }

    protected array $metadata = [];

    protected bool $isInitial = false;

    protected bool $isFinal = false;

    protected array $allowedTransitions = [];

    private string $_name = '';

    public function __construct(
        string $name,
        array $metadata = []
    ) {
        $this->name = $name;
        $this->metadata = $metadata;
    }

    // Public getters for protected properties

    public static function create(string $name): self
    {
        return new self($name);
    }

    public function isInitial(): bool
    {
        return $this->isInitial;
    }

    public function isFinal(): bool
    {
        return $this->isFinal;
    }

    public function getAllowedTransitions(): array
    {
        return $this->allowedTransitions;
    }

    public function withMetadata(array $metadata): static
    {
        $this->metadata = [...$this->metadata, ...$metadata];

        return $this;
    }

    public function getMetadata(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? null;
    }

    public function markAsInitial(): static
    {
        $this->isInitial = true;

        return $this;
    }

    public function markAsFinal(): static
    {
        $this->isFinal = true;

        return $this;
    }

    public function allowTransitions(array $transitions): static
    {
        $this->allowedTransitions = $transitions;

        return $this;
    }

    public function canTransitionTo(string $transition): bool
    {
        return empty($this->allowedTransitions) || in_array($transition, $this->allowedTransitions);
    }

    public function equals(State|string $other): bool
    {
        $otherName = $other instanceof State ? $other->getName() : $other;

        return $this->name === $otherName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'metadata' => $this->metadata,
            'is_initial' => $this->isInitial,
            'is_final' => $this->isFinal,
            'allowed_transitions' => $this->allowedTransitions,
        ];
    }
}
