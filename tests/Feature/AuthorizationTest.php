<?php

use SysMatter\StatusMachina\Exceptions\AuthorizationException;
use SysMatter\StatusMachina\StatusMachina;
use SysMatter\StatusMachina\Authorization\AuthorizationMethod;
use Illuminate\Support\Facades\Gate;
use SysMatter\StatusMachina\Tests\Fixtures\Models\User;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);
});

it('can check user authorization for transitions', function () {
    config(['status-machina.default_authorization' => AuthorizationMethod::Gate]);

    Gate::define('review', fn ($user) => $user->email === 'admin@example.com');

    $article = createArticle(['status' => 'pending_review']);
    $stateMachine = StatusMachina::for($article);

    expect($stateMachine->userCanTransitionTo('approved', [], $this->user))->toBeFalse();
    expect($stateMachine->userCanTransitionTo('approved', [], $this->admin))->toBeTrue();
});

it('uses current authenticated user by default', function () {
    config(['status-machina.default_authorization' => AuthorizationMethod::Gate]);

    Gate::define('review', fn ($user) => $user->email === 'admin@example.com');

    $this->actingAs($this->admin);

    $article = createArticle(['status' => 'pending_review']);
    $stateMachine = StatusMachina::for($article);

    expect($stateMachine->userCanTransitionTo('approved'))->toBeTrue();
});

it('throws exception when unauthorized transition is attempted', function () {
    config(['status-machina.default_authorization' => AuthorizationMethod::Gate]);

    Gate::define('publish', fn () => false);

    $article = createArticle(['status' => 'approved']);
    $stateMachine = StatusMachina::for($article);

    // Verify the hook exists
    $hooks = $stateMachine->getConfig()->hooks();
    expect($hooks)->toHaveKey('protect:publish');

    // This should throw AuthorizationException
    try {
        $stateMachine->transition('publish');
        $this->fail('Expected AuthorizationException was not thrown');
    } catch (AuthorizationException $e) {
        expect($e->getMessage())->toContain('Unauthorized to perform transition requiring ability: publish');
    }
});

it('allows transition when authorization is disabled', function () {
    config(['status-machina.default_authorization' => AuthorizationMethod::Null]);

    $article = createArticle(['status' => 'pending_review']);
    $stateMachine = StatusMachina::for($article);

    expect($stateMachine->userCanTransitionTo('approved', [], null))->toBeTrue();
});

it('passes context to authorization checks', function () {
    config(['status-machina.default_authorization' => AuthorizationMethod::Gate]);

    $capturedContext = null;
    Gate::define('review', function ($user, $model, $context) use (&$capturedContext) {
        $capturedContext = $context;
        return true;
    });

    $article = createArticle(['status' => 'pending_review']);
    $context = ['reviewed_by' => 'Mike', 'notes' => 'Good article'];

    StatusMachina::for($article)->userCanTransitionTo('approved', $context, $this->admin);

    expect($capturedContext)->toBe($context);
});
