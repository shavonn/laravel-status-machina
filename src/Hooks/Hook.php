<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Hooks;

class Hook
{
    protected mixed $callback = null;
    protected ?string $authorizationAbility = null;

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

    public function getCallback(): mixed
    {
        return $this->callback;
    }

    public function getAuthorizationAbility(): ?string
    {
        return $this->authorizationAbility;
    }
}
