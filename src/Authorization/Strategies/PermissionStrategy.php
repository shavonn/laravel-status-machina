<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Authorization\Strategies;

use RuntimeException;
use Shavonn\StatusMachina\Authorization\AuthorizationStrategyInterface;

class PermissionStrategy implements AuthorizationStrategyInterface
{
    public function authorize(string $ability, ?object $user, object $model, array $context = []): bool
    {
        if ($user === null) {
            return false;
        }

        // Check for Spatie Laravel Permission package
        if (! method_exists($user, 'hasPermissionTo')) {
            throw new RuntimeException(
                'Permission strategy requires Spatie Laravel Permission package'
            );
        }

        return $user->hasPermissionTo($ability);
    }
}
