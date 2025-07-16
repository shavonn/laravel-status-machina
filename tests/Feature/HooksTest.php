<?php

use Shavonn\StatusMachina\Config\AbstractStateConfig;
use Shavonn\StatusMachina\Hooks\Hook;
use Shavonn\StatusMachina\StatusMachina;
use Shavonn\StatusMachina\Tests\Fixtures\Models\Order;

it('executes before transition hooks', function () {
    $executed = false;
    $hookContext = null;

    // Create a test config that tracks execution
    class BeforeTransitionTestConfig extends AbstractStateConfig
    {
        public static $executed = false;
        public static $context = null;

        public function __construct()
        {
            $this->initialState = 'pending';
            $this->addStates(['pending', 'processed']);
            $this->setTransition(
                'process',
                $this->transition()->from('pending')->to('processed')
            );

            $this->beforeTransition('process', function ($model, $context) {
                self::$executed = true;
                self::$context = $context;
            });
        }
    }

    StatusMachina::registerStateConfig('before_test', BeforeTransitionTestConfig::class);
    StatusMachina::registerStateManagement(Order::class, 'status', 'before_test');

    $order = createOrder(['status' => 'pending']);
    StatusMachina::for($order, 'status')->transition('process', ['user' => 'admin']);

    expect(BeforeTransitionTestConfig::$executed)->toBeTrue();
    expect(BeforeTransitionTestConfig::$context)->toBe(['user' => 'admin']);
});

it('executes hooks in correct order', function () {
    class HookOrderTestConfig extends AbstractStateConfig
    {
        public static $order = [];

        public function __construct()
        {
            self::$order = []; // Reset

            $this->initialState = 'start';
            $this->addStates(['start', 'middle', 'end']);
            $this->setTransition(
                'advance',
                $this->transition()->from('start')->to('middle')
            );

            $this->beforeTransition('advance', function () {
                self::$order[] = 'before.transition';
            });

            $this->beforeStateFrom('start', function () {
                self::$order[] = 'before.from';
            });

            $this->beforeStateTo('middle', function () {
                self::$order[] = 'before.to';
            });

            $this->afterTransition('advance', function () {
                self::$order[] = 'after.transition';
            });

            $this->afterStateFrom('start', function () {
                self::$order[] = 'after.from';
            });

            $this->afterStateTo('middle', function () {
                self::$order[] = 'after.to';
            });
        }
    }

    StatusMachina::registerStateConfig('order_test', HookOrderTestConfig::class);
    StatusMachina::registerStateManagement(Order::class, 'status', 'order_test');

    $order = createOrder(['status' => 'start']);
    StatusMachina::for($order, 'status')->transition('advance');

    expect(HookOrderTestConfig::$order)->toBe([
        'before.transition',
        'before.from',
        'before.to',
        'after.transition',
        'after.from',
        'after.to',
    ]);
});

it('can set callback', function () {
    $callback = fn () => 'test';
    $hook = (new Hook())->callback($callback);

    expect($hook->getCallback())->toBe($callback);
});

it('can set authorization ability', function () {
    $hook = (new Hook())->authorization('publish-articles');

    expect($hook->getAuthorizationAbility())->toBe('publish-articles');
});

it('can use different callback types', function () {
    // Closure
    $hook1 = (new Hook())->callback(fn () => 'test');
    expect($hook1->getCallback())->toBeCallable();

    // Class name
    $hook2 = (new Hook())->callback('SomeClass');
    expect($hook2->getCallback())->toBe('SomeClass');

    // Array callback
    $hook3 = (new Hook())->callback(['SomeClass', 'method']);
    expect($hook3->getCallback())->toBe(['SomeClass', 'method']);
});
