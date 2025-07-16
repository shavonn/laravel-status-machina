<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Hooks;

use Illuminate\Support\Facades\App;
use Shavonn\StatusMachina\Exceptions\InvalidStateException;
use Throwable;

class HookManager
{
    /** @var array<string, array<int, Hook>> */
    protected array $hooks = [];

    public function __construct(array $hooks)
    {
        $this->hooks = $this->sortHooksByPriority($hooks);
    }

    protected function sortHooksByPriority(array $hooks): array
    {
        return array_map(
            fn ($typeHooks) => $this->sortByPriority($typeHooks),
            $hooks
        );
    }

    protected function sortByPriority(array $hooks): array
    {
        usort($hooks, fn (Hook $a, Hook $b) => $b->getPriority() <=> $a->getPriority());

        return $hooks;
    }

    public function executeHooks(string $type, object $model, array $context = []): void
    {
        if (! isset($this->hooks[$type])) {
            return;
        }

        foreach ($this->hooks[$type] as $hook) {
            if ($hook->shouldExecute($model, $context)) {
                $this->executeHook($hook, $model, $context);
            }
        }
    }

    protected function executeHook(Hook $hook, object $model, array $context): void
    {
        $callback = $hook->getCallback();

        try {
            match (true) {
                is_callable($callback) => $callback($model, $context),
                is_string($callback) && class_exists($callback) => $this->executeClassCallback($callback, $model, $context),
                is_array($callback) && count($callback) === 2 => $this->executeArrayCallback($callback, $model, $context),
                default => throw new InvalidStateException('Invalid hook callback format'),
            };
        } catch (Throwable $e) {
            if ($hook->stopOnError) {
                throw $e;
            }
            // Log error but continue if stopOnError is false
            report($e);
        }
    }

    protected function executeClassCallback(string $class, object $model, array $context): void
    {
        /** @var object $instance */
        $instance = App::make($class);

        if (! method_exists($instance, 'handle')) {
            throw new InvalidStateException('Hook class must have a handle method');
        }

        $instance->handle($model, $context);
    }

    protected function executeArrayCallback(array $callback, object $model, array $context): void
    {
        [$class, $method] = $callback;
        /** @var object $instance */
        $instance = App::make($class);
        $instance->$method($model, $context);
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
}
