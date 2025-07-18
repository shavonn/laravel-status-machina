<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\Authorization;

interface AuthorizationStrategyInterface
{
    public function authorize(string $ability, ?object $user, object $model, array $context = []): bool;
}
