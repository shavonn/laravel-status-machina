<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Hooks;

use InvalidArgumentException;

class Hook
{
    public int $priority = 0;

    public bool $stopOnError = true;

    protected mixed $callback = null;

    protected ?string $authorizationAbility = null;

    protected mixed $condition = null;

    protected array $tags = [];

    public function callback(callable|string|array $callback): static
    {
        $this->callback = $callback;

        return $this;
    }

    public function authorization(string $ability): static
    {
        $this->authorizationAbility = $ability;

        return $this;
    }

    public function when(callable $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    public function withPriority(int $priority): static
    {
        if ($priority < 0 || $priority > 100) {
            throw new InvalidArgumentException('Priority must be between 0 and 100');
        }
        $this->priority = $priority;

        return $this;
    }

    public function continueOnError(): static
    {
        $this->stopOnError = false;

        return $this;
    }

    public function withTags(string ...$tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function hasAnyTag(array $tags): bool
    {
        return array_any($tags, fn ($tag) => $this->hasTag($tag));
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags);
    }

    public function shouldExecute(object $model, array $context): bool
    {
        if ($this->condition === null || ! is_callable($this->condition)) {
            return true;
        }

        return ($this->condition)($model, $context);
    }

    public function getCallback(): mixed
    {
        return $this->callback;
    }

    public function getAuthorizationAbility(): ?string
    {
        return $this->authorizationAbility;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
