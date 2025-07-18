<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\Authorization;

enum AuthorizationMethod: string
{
    case Null = 'null';
    case Gate = 'gate';
    case Policy = 'policy';
    case Permission = 'permission';

    public function isActive(): bool
    {
        return $this !== self::Null;
    }

    public function getStrategyClass(): ?string
    {
        return match ($this) {
            self::Gate => Strategies\GateStrategy::class,
            self::Policy => Strategies\PolicyStrategy::class,
            self::Permission => Strategies\PermissionStrategy::class,
            self::Null => null,
        };
    }
}
