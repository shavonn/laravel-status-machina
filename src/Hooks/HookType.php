<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Hooks;

enum HookType: string
{
    case BeforeTransition = 'before.on';
    case AfterTransition = 'after.on';
    case BeforeStateTo = 'before.to';
    case AfterStateTo = 'after.to';
    case BeforeStateFrom = 'before.from';
    case AfterStateFrom = 'after.from';
    case Protect = 'protect';

    public function buildKey(string $target): string
    {
        return "{$this->value}:{$target}";
    }

    public function isBefore(): bool
    {
        return str_starts_with($this->value, 'before');
    }

    public function isAfter(): bool
    {
        return str_starts_with($this->value, 'after');
    }
}
