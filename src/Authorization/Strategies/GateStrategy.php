<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\Authorization\Strategies;

use Illuminate\Support\Facades\Gate;
use SysMatter\StatusMachina\Authorization\AuthorizationStrategyInterface;

class GateStrategy implements AuthorizationStrategyInterface
{
    public function authorize(string $ability, ?object $user, object $model, array $context = []): bool
    {
        return $user !== null && Gate::forUser($user)->allows($ability, [$model, $context]);
    }
}
