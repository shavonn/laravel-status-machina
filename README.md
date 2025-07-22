# Laravel Status Machina

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sysmatter/laravel-status-machina.svg?style=flat-square)](https://packagist.org/packages/sysmatter/laravel-status-machina)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/sysmatter/laravel-status-machina/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/sysmatter/laravel-status-machina/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/sysmatter/laravel-status-machina/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/sysmatter/laravel-status-machina/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/sysmatter/laravel-status-machina.svg?style=flat-square)](https://packagist.org/packages/sysmatter/laravel-status-machina)

A powerful and flexible state machine package for Laravel 12 with PHP 8.4, featuring state management, transitions,
hooks, and authorization.

## Features

* **Modern PHP 8.4** - Leverages property hooks, asymmetric visibility, and new array functions
* **Flexible State Management** - Works with Eloquent models and plain PHP objects
* **Powerful Hooks System** - Before/after hooks with priorities and conditional execution
* **Built-in Authorization** - Gate, Policy, and Permission-based transition protection
* **History Tracking** - Optional database tracking with rich querying capabilities
* **Type-Safe** - Full type hints and PHPStan compatibility
* **Laravel 12 Optimized** - Built specifically for Laravel 12

## Requirements

* PHP 8.4+
* Laravel 12.0+

## Installation

```bash
composer require sysmatter/laravel-status-machina
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="SysMatter\StatusMachina\StatusMachinaServiceProvider" --tag=status-machina-config
```

### Publish Migrations (if using database history tracking)

```bash
php artisan vendor:publish --provider="SysMatter\StatusMachina\StatusMachinaServiceProvider" --tag=status-machina-migrations
php artisan migrate
```

## Quick Start

### 1. Create a State Configuration

```php
<?php

namespace App\States;

use SysMatter\StatusMachina\Config\AbstractStateConfig;

class OrderStateConfig extends AbstractStateConfig
{
    protected string $initialState = 'pending';

    public function __construct()
    {
        // Define states
        $this->addStates([
            'pending',
            'processing',
            'shipped',
            'delivered',
            'cancelled',
            'refunded'
        ]);

        // Define transitions
        $this->setTransition('process', 
            $this->transition()
                ->from('pending')
                ->to('processing')
        );

        $this->setTransition('ship',
            $this->transition()
                ->from('processing')
                ->to('shipped')
        );

        $this->setTransition('deliver',
            $this->transition()
                ->from('shipped')
                ->to('delivered')
        );

        $this->setTransition('cancel',
            $this->transition()
                ->from(['pending', 'processing'])
                ->to('cancelled')
        );

        // Add hooks
        $this->beforeTransition('ship', function ($order, $context) {
            if (!$order->hasShippingAddress()) {
                throw new \Exception('Shipping address required');
            }
        });

        $this->afterTransition('deliver', function ($order, $context) {
            $order->customer->notify(new OrderDeliveredNotification());
        });

        // Protect transitions
        $this->protectTransition('cancel', 'cancel-order');
        $this->protectTransition('refund', 'refund-order');
    }
}
```

### 2. Register State Configuration

```php
// In AppServiceProvider or a dedicated ServiceProvider

use SysMatter\StatusMachina\StatusMachina;

public function boot(): void
{
    StatusMachina::registerStateConfig('order', OrderStateConfig::class);
    StatusMachina::registerStateManagement(Order::class, 'status', 'order');
}
```

### 3. Use in Your Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SysMatter\StatusMachina\Traits\HasStateMachine;

class Order extends Model
{
    use HasStateMachine;

    protected $fillable = ['status', 'total', 'customer_id'];
}
```

### 4. Working with States

```php
$order = Order::find(1);

// Get current state
$currentState = $order->currentState(); // 'pending'

// Check state
if ($order->stateIs('pending')) {
    // Order is pending
}

// Check multiple states
if ($order->stateIsAny(['pending', 'processing'])) {
    // Order is active
}

// Get available transitions
$transitions = $order->availableTransitions(); // ['process', 'cancel']

// Check if can transition
if ($order->canTransitionTo('processing')) {
    $order->transition('process');
}

// Transition with context
$order->transition('ship', [
    'carrier' => 'FedEx',
    'tracking_number' => '1234567890',
    'shipped_by' => auth()->id()
]);

// Save the model after transitions
$order->save();
```

## Advanced Features

### Transition Guards

Protect transitions with callable guards:

```php
$this->setTransition('publish',
    $this->transition()
        ->from('approved')
        ->to('published')
        ->guard(fn($article) => $article->isComplete())
        ->guard(fn($article) => $article->hasRequiredMetadata())
);
```

### Authorization

Configure authorization globally in config/status-machina.php:

```php
'default_authorization' => 'policy', // null, gate, policy, or permission
```

Or protect specific transitions:

```php
$this->protectTransition('approve', 'review-articles');
$this->protectTransition('publish', 'publish-articles');
```

Check authorization with context:

```php
$stateMachine = StatusMachina::for($article);

if ($stateMachine->userCanTransitionTo('approved', ['reviewed_by' => 'Mike'])) {
    $stateMachine->transition('approve', ['reviewed_by' => 'Mike']);
}
```

### History Tracking

Enable history tracking globally:

```php
// In config/status-machina.php
'db_history_tracking' => [
    'enabled' => true,
    'history_table_name' => 'state_transitions',
],
```

Or per state configuration:

```php
class ArticleStateConfig extends AbstractStateConfig
{
    public function __construct()
    {
        // ... states and transitions ...
        
        $this->trackHistory('database', ['enabled' => true]);
    }
}
```

Query transition history:

```php
use SysMatter\StatusMachina\Models\StateTransition;

// Get all transitions for a model
$history = StateTransition::forModel($order)
    ->forProperty('status')
    ->latest()
    ->get();

// Get transition statistics
$stats = app(StateTransitionRepository::class)
    ->getStateDurations($order, 'status');

// Prune old history
php artisan status-machina:prune-history --days=90
```

### Working with Non-Eloquent Objects

```php
class OrderDTO
{
    public string $status = '';
    public array $items = [];
}

// Register state management
StatusMachina::registerStateConfig('order', OrderStateConfig::class);
StatusMachina::registerStateManagement(OrderDTO::class, 'status', 'order');

// Use it
$order = new OrderDTO();
$stateMachine = StatusMachina::for($order, 'status');
$stateMachine->transition('process');
```

### State Configuration Reference

#### States

```php
// Single state
$this->state('active');

// Multiple states
$this->addStates(['draft', 'published', 'archived']);
```

#### Transitions

```php
// Simple transition
$this->setTransition('activate',
    $this->transition()->from('inactive')->to('active')
);

// Multiple from states
$this->setTransition('archive',
    $this->transition()->from(['draft', 'published'])->to('archived')
);

// From any state
$this->setTransition('reset',
    $this->transition()->from('*')->to('draft')
);

// With metadata
$this->setTransition('publish',
    $this->transition()
        ->from('approved')
        ->to('published')
        ->withMetadata(['requires_review' => true])
);
```

#### Hooks

```php
// Before/after transition
$this->beforeTransition('submit', $callback);
$this->afterTransition('approve', $callback);

// Before/after entering state
$this->beforeStateTo('published', $callback);
$this->afterStateTo('archived', $callback);

// Before/after leaving state
$this->beforeStateFrom('draft', $callback);
$this->afterStateFrom('published', $callback);

// With class handler
$this->beforeTransition('process', ProcessOrderHandler::class);

// With method array
$this->afterTransition('deliver', [OrderService::class, 'handleDelivery']);
```

#### Hook Handlers

```php
// Callable
$this->beforeTransition('delete', function ($model, array $context) {
    Log::warning("Deleting {$model->name}", $context);
});

// Class with handle method
class ArchiveHandler
{
    public function handle($model, array $context): void
    {
        Storage::move($model->path, 'archive/' . $model->path);
    }
}
```

### Configuration Options

```php
return [
    // Default authorization method: null, gate, policy, permission
    'default_authorization' => env('STATUS_MACHINA_AUTH', 'null'),

    // Database history tracking
    'db_history_tracking' => [
        'enabled' => false,
        'history_table_name' => 'state_transitions',
    ],

    // Activity log history tracking (for Spatie Activity Log)
    'activitylog_history_tracking' => [
        'enabled' => false,
        'log_name' => 'state_transitions',
    ],

    // Days to retain history (null = forever)
    'max_history_retention' => null,
];
```

### Testing

```php
use SysMatter\StatusMachina\StatusMachina;

public function test_order_can_transition_to_processing()
{
    $order = Order::factory()->create(['status' => 'pending']);
    
    $this->assertTrue($order->canTransitionTo('processing'));
    $this->assertTrue($order->stateIs('pending'));
    
    $order->transition('process');
    
    $this->assertTrue($order->stateIs('processing'));
    $this->assertEquals(['ship', 'cancel'], $order->availableTransitions());
}

public function test_unauthorized_user_cannot_cancel_order()
{
    $this->actingAs($regularUser);
    
    $order = Order::factory()->create(['status' => 'processing']);
    $stateMachine = StatusMachina::for($order);
    
    $this->assertFalse($stateMachine->userCanTransitionTo('cancelled'));
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
