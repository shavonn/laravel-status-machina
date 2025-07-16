<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina;

use Illuminate\Contracts\Foundation\Application;
use Shavonn\StatusMachina\Contracts\StateConfigInterface;
use Shavonn\StatusMachina\Exceptions\InvalidStateException;
use Shavonn\StatusMachina\State\StateManager;

class StatusMachinaManager
{
    /** @var array<string, class-string<StateConfigInterface>> */
    protected array $stateConfigs = [];

    /** @var array<string, array<string, string>> */
    protected array $stateManagement = [];

    /** @var array<string, StateConfigInterface> Cached config instances */
    protected array $configCache = [];

    public function __construct(
        protected readonly Application $app
    ) {
    }

    public function registerStateConfig(string $type, string $configClass): void
    {
        if (! is_subclass_of($configClass, StateConfigInterface::class)) {
            throw new InvalidStateException(
                message: 'Config class must implement StateConfigInterface',
                code: 400
            );
        }

        $this->stateConfigs[$type] = $configClass;
        unset($this->configCache[$type]); // Clear cache when registering new config
    }

    public function registerStateManagement(string $class, string $property, string $type): void
    {
        if (! isset($this->stateConfigs[$type])) {
            throw new InvalidStateException(
                message: "State config type '{$type}' not registered",
                code: 404
            );
        }

        $this->stateManagement[$class][$property] = $type;
    }

    public function for(object $model, string $property = 'status'): StateManager
    {
        $class = $model::class;

        $type = $this->stateManagement[$class][$property]
            ?? throw new InvalidStateException(
                message: "No state management registered for {$class}::{$property}",
                code: 404
            );

        $config = $this->getOrCreateConfig($type);

        return new StateManager(
            model: $model,
            property: $property,
            config: $config,
            app: $this->app
        );
    }

    protected function getOrCreateConfig(string $type): StateConfigInterface
    {
        return $this->configCache[$type] ??= new $this->stateConfigs[$type]();
    }

    public function getStateConfig(string $type): ?string
    {
        return $this->stateConfigs[$type] ?? null;
    }

    public function getAllStateConfigs(): array
    {
        return $this->stateConfigs;
    }
}
