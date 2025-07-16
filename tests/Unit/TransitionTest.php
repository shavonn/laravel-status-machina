<?php

use Shavonn\StatusMachina\State\Transition;

it('can be created with fluent interface', function () {
    $transition = (new Transition())
        ->from('draft')
        ->to('published');

    expect($transition->getFrom())->toBe('draft');
    expect($transition->getTo())->toBe('published');
});

it('supports multiple from states', function () {
    $transition = (new Transition())
        ->from(['draft', 'review'])
        ->to('published');

    expect($transition->getFrom())->toBe(['draft', 'review']);
    expect($transition->allowsFrom('draft'))->toBeTrue();
    expect($transition->allowsFrom('review'))->toBeTrue();
    expect($transition->allowsFrom('published'))->toBeFalse();
});

it('supports wildcard from state', function () {
    $transition = (new Transition())
        ->from('*')
        ->to('archived');

    expect($transition->allowsFrom('any-state'))->toBeTrue();
});

it('can have guards', function () {
    $transition = (new Transition())
        ->from('draft')
        ->to('published')
        ->guard(fn ($model) => $model->isValid)
        ->guard(fn ($model) => $model->hasAuthor);

    $model = (object) ['isValid' => true, 'hasAuthor' => true];
    expect($transition->passesGuards($model))->toBeTrue();

    $model->isValid = false;
    expect($transition->passesGuards($model))->toBeFalse();
});

it('can store metadata', function () {
    $transition = (new Transition())
        ->from('draft')
        ->to('published')
        ->withMetadata(['requires_review' => true, 'min_score' => 80]);

    expect($transition->getMetadata())->toBe([
        'requires_review' => true,
        'min_score' => 80
    ]);
});

it('has string representation', function () {
    $simple = (new Transition())->from('draft')->to('published');
    expect((string) $simple)->toBe('draft → published');

    $multiple = (new Transition())->from(['draft', 'review'])->to('published');
    expect((string) $multiple)->toBe('draft|review → published');

    $wildcard = (new Transition())->from('*')->to('archived');
    expect((string) $wildcard)->toBe('* → archived');
});
