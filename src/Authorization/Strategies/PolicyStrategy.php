<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Authorization\Strategies;

use Illuminate\Support\Facades\Gate;
use Shavonn\StatusMachina\Authorization\AuthorizationStrategyInterface;

class PolicyStrategy implements AuthorizationStrategyInterface
{
    public function authorize(string $ability, ?object $user, object $model, array $context = []): bool
    {
        return $user !== null && Gate::forUser($user)->allows($ability, $model);
    }
}
