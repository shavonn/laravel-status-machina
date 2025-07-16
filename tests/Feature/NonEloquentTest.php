<?php

use Shavonn\StatusMachina\StatusMachina;
use Shavonn\StatusMachina\Tests\Fixtures\DTOs\OrderDTO;

it('works with non-eloquent objects', function () {
    StatusMachina::registerStateManagement(OrderDTO::class, 'status', 'order');

    $dto = new OrderDTO();
    $stateMachine = StatusMachina::for($dto);

    expect($stateMachine->getState())->toBe('pending');

    $stateMachine->transition('process');

    expect($dto->status)->toBe('processing');
    expect($stateMachine->getState())->toBe('processing');
});

it('maintains state in memory for DTOs', function () {
    StatusMachina::registerStateManagement(OrderDTO::class, 'status', 'order');

    $dto = new OrderDTO();
    $stateMachine = StatusMachina::for($dto);

    $stateMachine->transition('process');
    $stateMachine->transition('ship');

    expect($dto->status)->toBe('shipped');
    expect($stateMachine->getPreviousState())->toBe('processing');
});

it('validates transitions for DTOs', function () {
    StatusMachina::registerStateManagement(OrderDTO::class, 'status', 'order');

    $dto = new OrderDTO();
    $dto->status = 'shipped';

    $stateMachine = StatusMachina::for($dto);

    expect($stateMachine->canTransitionTo('processing'))->toBeFalse();
    expect($stateMachine->canTransitionTo('delivered'))->toBeTrue();
});
