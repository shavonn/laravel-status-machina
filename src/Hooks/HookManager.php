<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Hooks;

use Illuminate\Contracts\Container\BindingResolutionException;
use Shavonn\StatusMachina\Exceptions\InvalidStateException;
use Illuminate\Support\Facades\App;

class HookManager
{
    /** @var array<string, array<int, Hook>> */
    protected array $hooks = [];

    public function __construct(array $hooks)
    {
        $this->hooks = $hooks;
    }

    public function executeHooks(string $type, object $model, array $context = []): void
    {
        if (!isset($this->hooks[$type])) {
            return;
        }

        foreach ($this->hooks[$type] as $hook) {
            $this->executeHook($hook, $model, $context);
        }
    }

    public function getHook(string $type): ?Hook
    {
        return $this->hooks[$type][0] ?? null;
    }

    public function getHooks(string $type): array
    {
        return $this->hooks[$type] ?? [];
    }

    public function hasHooks(string $type): bool
    {
        return isset($this->hooks[$type]) && count($this->hooks[$type]) > 0;
    }

    protected function executeHook(Hook $hook, object $model, array $context): void
    {
        $callback = $hook->getCallback();

        match (true) {
            is_callable($callback) => $callback($model, $context),
            is_string($callback) && class_exists($callback) => $this->executeClassCallback($callback, $model, $context),
            is_array($callback) && count($callback) === 2 => $this->executeArrayCallback($callback, $model, $context),
            default => throw new InvalidStateException("Invalid hook callback format"),
        };
    }

    protected function executeClassCallback(string $class, object $model, array $context): void
    {
        /** @var object $instance */
        $instance = App::make($class);

        if (!method_exists($instance, 'handle')) {
            throw new InvalidStateException("Hook class must have a handle method");
        }

        $instance->handle($model, $context);
    }

    /**
     * @throws BindingResolutionException
     */
    protected function executeArrayCallback(array $callback, object $model, array $context): void
    {
        [$class, $method] = $callback;
        /** @var object $instance */
        $instance = App::make($class);
        $instance->$method($model, $context);
    }
}
