<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina;

use Illuminate\Support\Facades\Facade;
use Shavonn\StatusMachina\State\StateManager;

/**
 * @method static StateManager for(object $model, string $property = 'status')
 * @method static void registerStateConfig(string $type, string $configClass)
 * @method static void registerStateManagement(string $class, string $property, string $type)
 * @method static ?string getStateConfig(string $type)
 * @method static array getAllStateConfigs()
 */
class StatusMachina extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'status-machina';
    }
}
