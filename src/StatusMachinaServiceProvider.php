<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina;

use Illuminate\Support\ServiceProvider;
use Shavonn\StatusMachina\History\StateTransitionRepository;

class StatusMachinaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/status-machina.php', 'status-machina');

        $this->app->singleton('status-machina', fn ($app) => new StatusMachinaManager($app));

        // Register history repository
        $this->app->singleton(StateTransitionRepository::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__.'/../config/status-machina.php' => config_path('status-machina.php'),
            ], 'status-machina-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'status-machina-migrations');

            // Register commands
            $this->commands([
                Commands\PruneStateHistory::class,
            ]);
        }
    }

    public function provides(): array
    {
        return ['status-machina'];
    }
}
