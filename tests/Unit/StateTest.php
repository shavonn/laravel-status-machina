<?php

use SysMatter\StatusMachina\State\State;

it('can be created with name', function () {
    $state = new State('published');
    expect($state->getName())->toBe('published');
});

it('validates name is not empty', function () {
    expect(fn () => new State(''))
        ->toThrow(InvalidArgumentException::class, 'State name cannot be empty');
});

it('can store metadata', function () {
    $state = State::create('published')
        ->withMetadata(['color' => 'green', 'icon' => 'check']);

    expect($state->getMetadata())->toBe(['color' => 'green', 'icon' => 'check']);
    expect($state->getMetadata('color'))->toBe('green');
    expect($state->getMetadata('missing'))->toBeNull();
});

it('can be marked as initial or final', function () {
    $state = State::create('draft')->markAsInitial();
    expect($state->isInitial())->toBeTrue();
    expect($state->isFinal())->toBeFalse();

    $finalState = State::create('archived')->markAsFinal();
    expect($finalState->isInitial())->toBeFalse();
    expect($finalState->isFinal())->toBeTrue();
});

it('can define allowed transitions', function () {
    $state = State::create('draft')
        ->allowTransitions(['submit', 'delete']);

    expect($state->canTransitionTo('submit'))->toBeTrue();
    expect($state->canTransitionTo('delete'))->toBeTrue();
    expect($state->canTransitionTo('publish'))->toBeFalse();
});

it('can be compared', function () {
    $state1 = new State('published');
    $state2 = new State('published');
    $state3 = new State('draft');

    expect($state1->equals($state2))->toBeTrue();
    expect($state1->equals('published'))->toBeTrue();
    expect($state1->equals($state3))->toBeFalse();
    expect($state1->equals('draft'))->toBeFalse();
});

it('can be converted to array', function () {
    $state = State::create('published')
        ->withMetadata(['color' => 'green'])
        ->markAsFinal()
        ->allowTransitions(['archive']);

    expect($state->toArray())->toBe([
        'name' => 'published',
        'metadata' => ['color' => 'green'],
        'is_initial' => false,
        'is_final' => true,
        'allowed_transitions' => ['archive'],
    ]);
});
