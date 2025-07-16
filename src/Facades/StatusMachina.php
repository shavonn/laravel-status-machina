<?php

namespace Shavonn\StatusMachina\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Shavonn\StatusMachina\StatusMachina
 */
class StatusMachina extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Shavonn\StatusMachina\StatusMachina::class;
    }
}
