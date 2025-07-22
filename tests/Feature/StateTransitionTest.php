<?php

use SysMatter\StatusMachina\StatusMachina;
use SysMatter\StatusMachina\Exceptions\InvalidTransitionException;
use SysMatter\StatusMachina\Tests\Fixtures\Models\Article;

it('can transition between valid states', function () {
    $article = createArticle();

    expect($article->currentState())->toBe('draft');
    expect($article->canTransitionTo('pending_review'))->toBeTrue();

    $article->transition('submit');

    expect($article->currentState())->toBe('pending_review');
    expect($article->previousState())->toBe('draft');
});

it('throws exception for invalid transitions', function () {
    $article = createArticle();

    expect($article->currentState())->toBe('draft');
    expect($article->canTransitionTo('published'))->toBeFalse();

    $article->transition('publish');
})->throws(InvalidTransitionException::class, "Cannot transition 'publish' from state 'draft'");

it('can get available transitions', function () {
    $article = createArticle();

    expect($article->availableTransitions())->toBe(['submit', 'archive']);

    $article->transition('submit');

    expect($article->availableTransitions())->toBe(['approve', 'reject', 'request_changes', 'archive']);
});

it('supports wildcard from transitions', function () {
    $article = createArticle(['status' => 'published']);

    expect($article->canTransitionTo('archived'))->toBeTrue();

    $article->transition('archive');

    expect($article->currentState())->toBe('archived');
});

it('can transition with context data', function () {
    $article = createArticle(['status' => 'pending_review']);
    $context = ['reviewed_by' => 'John Doe', 'notes' => 'Looks good'];

    $article->transition('approve', $context);

    expect($article->currentState())->toBe('approved');
});

it('can check if in specific states', function () {
    $article = createArticle();

    expect($article->stateIs('draft'))->toBeTrue();
    expect($article->stateIs('published'))->toBeFalse();
    expect($article->stateIsAny(['draft', 'pending_review']))->toBeTrue();
    expect($article->stateIsAny(['published', 'archived']))->toBeFalse();
});

it('maintains state when transition fails', function () {
    $article = createArticle(['is_valid' => false]); // Explicitly invalid

    $currentState = $article->currentState();

    try {
        $article->transition('submit');
    } catch (Exception $e) {
        // State should remain unchanged
        expect($article->currentState())->toBe($currentState);
    }
});

it('can use state machine directly', function () {
    $article = createArticle();
    $stateMachine = StatusMachina::for($article);

    expect($stateMachine->getState())->toBe('draft');
    expect($stateMachine->canTransitionTo('pending_review'))->toBeTrue();

    $stateMachine->transition('submit');

    expect($stateMachine->getState())->toBe('pending_review');
});

it('supports multiple state properties on same model', function () {
    // Register another state machine for a different property
    StatusMachina::registerStateManagement(
        Article::class,
        'review_status',
        'article'
    );

    $article = createArticle();

    $contentStatus = StatusMachina::for($article, 'status');
    $reviewStatus = StatusMachina::for($article, 'review_status');

    expect($contentStatus)->not->toBe($reviewStatus);
});

it('can prevent transition by throwing exception in hook', function () {
    $article = createArticle(['is_valid' => false]);

    expect(fn () => $article->transition('submit'))
        ->toThrow(Exception::class, 'Article must be valid')
        ->and($article->currentState())->toBe('draft');
});
