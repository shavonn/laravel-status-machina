<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\Authorization;

use Illuminate\Contracts\Foundation\Application;
use SysMatter\StatusMachina\Exceptions\AuthorizationException;

class AuthorizationManager
{
    /** @var array<string, AuthorizationStrategyInterface> */
    protected array $strategies = [];
    protected AuthorizationMethod $defaultMethod;

    public function __construct(
        protected readonly Application $app
    ) {
        $config = config('status-machina.default_authorization');

        // Handle both string and enum configs
        $this->defaultMethod = $config instanceof AuthorizationMethod
            ? $config
            : AuthorizationMethod::from($config ?? 'null');

        $this->initializeStrategies();
    }

    protected function initializeStrategies(): void
    {
        foreach (AuthorizationMethod::cases() as $method) {
            if ($strategyClass = $method->getStrategyClass()) {
                $this->strategies[$method->value] = new $strategyClass();
            }
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(string $ability, ?object $user, object $model, array $context = []): void
    {
        if (! $this->defaultMethod->isActive()) {
            return;
        }

        $strategy = $this->strategies[$this->defaultMethod->value]
            ?? throw new AuthorizationException(
                message: "Unknown authorization strategy: {$this->defaultMethod->value}",
                code: 500
            );

        if (! $strategy->authorize($ability, $user, $model, $context)) {
            throw new AuthorizationException(
                message: "Unauthorized to perform transition requiring ability: {$ability}",
                code: 403
            );
        }
    }

    public function using(AuthorizationMethod $method, callable $callback): mixed
    {
        $original = $this->defaultMethod;

        try {
            $this->setStrategy($method);

            return $callback();
        } finally {
            $this->setStrategy($original);
        }
    }

    public function setStrategy(AuthorizationMethod $method): void
    {
        config(['status-machina.default_authorization' => $method]);
    }
}
