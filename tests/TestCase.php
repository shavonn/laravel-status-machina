<?php

namespace SysMatter\StatusMachina\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SysMatter\StatusMachina\StatusMachina;
use SysMatter\StatusMachina\StatusMachinaServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use SysMatter\StatusMachina\Authorization\AuthorizationMethod;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            StatusMachinaServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'StatusMachina' => StatusMachina::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set authorization to null enum value
        $app['config']->set('status-machina.default_authorization', AuthorizationMethod::Null);
        $app['config']->set('status-machina.db_history_tracking.enabled', false);
    }

    protected function setUpDatabase(): void
    {
        // Create state transitions table first
        Schema::create('state_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('property', 50)->default('status');
            $table->string('transition')->nullable();
            $table->string('from_state');
            $table->string('to_state');
            $table->json('context')->nullable();
            $table->nullableMorphs('transitioner');
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['model_type', 'model_id', 'property']);
            $table->index('transition');
            $table->index('from_state');
            $table->index('to_state');
            $table->index('created_at');
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('draft');
            $table->boolean('is_valid')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->decimal('total', 10, 2);
            $table->string('shipping_address')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });
    }
}
