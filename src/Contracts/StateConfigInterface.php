<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Contracts;

use Shavonn\StatusMachina\Hooks\Hook;
use Shavonn\StatusMachina\State\Transition;

interface StateConfigInterface
{
    /**
     * Get the initial state
     */
    public function initialState(): string;

    /**
     * Get all available states
     *
     * @return array<int, string>
     */
    public function states(): array;

    /**
     * Get all transitions
     *
     * @return array<string, Transition>
     */
    public function transitions(): array;

    /**
     * Get all hooks
     *
     * @return array<string, array<int, Hook>>
     */
    public function hooks(): array;

    /**
     * Get history tracking configuration
     *
     * @return array{type: string, enabled?: bool, log_name?: string}|null
     */
    public function historyTracking(): ?array;
}
