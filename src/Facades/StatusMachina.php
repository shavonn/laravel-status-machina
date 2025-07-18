<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SysMatter\StatusMachina\StatusMachina
 */
class StatusMachina extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SysMatter\StatusMachina\StatusMachina::class;
    }
}
