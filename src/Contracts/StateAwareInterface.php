<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Contracts;

interface StateAwareInterface
{
    /**
     * Get the current state value
     */
    public function getState(string $property): ?string;

    /**
     * Set the state value
     */
    public function setState(string $property, string $state): void;

    /**
     * Check if the model supports the given state property
     */
    public function hasStateProperty(string $property): bool;
}
