<?php

use SysMatter\StatusMachina\Hooks\Hook;

it('can set callback', function () {
    $callback = fn () => 'test';
    $hook = (new Hook())->callback($callback);

    expect($hook->getCallback())->toBe($callback);
});

it('can set authorization ability', function () {
    $hook = (new Hook())->authorization('publish-articles');

    expect($hook->getAuthorizationAbility())->toBe('publish-articles');
});
