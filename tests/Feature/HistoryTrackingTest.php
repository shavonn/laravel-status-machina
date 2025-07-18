<?php

use SysMatter\StatusMachina\Models\StateTransition;
use SysMatter\StatusMachina\History\StateTransitionRepository;
use SysMatter\StatusMachina\Config\AbstractStateConfig;
use SysMatter\StatusMachina\Tests\Fixtures\Models\Order;

beforeEach(function () {
    config(['status-machina.db_history_tracking.enabled' => true]);
});

it('records state transitions when history is enabled', function () {
    $article = createArticle(['is_valid' => true]); // Ensure valid

    expect(StateTransition::count())->toBe(0);

    $article->transitionTo('submit');

    expect(StateTransition::count())->toBe(1);

    $transition = StateTransition::first();
    expect($transition->model_type)->toBe($article::class);
    expect($transition->model_id)->toBe($article->id);
    expect($transition->from_state)->toBe('draft');
    expect($transition->to_state)->toBe('pending_review');
    expect($transition->transition)->toBe('submit');
});

it('records context data with transitions', function () {
    $article = createArticle(['status' => 'pending_review']);
    $context = ['reviewed_by' => 'John', 'score' => 95];

    $article->transitionTo('approve', $context);

    $transition = StateTransition::latest()->first();
    expect($transition->context)->toBe($context);
});

it('can query transition history for a model', function () {
    $article = createArticle();
    $otherArticle = createArticle();

    $article->transitionTo('submit');
    $article->transitionTo('approve');
    $otherArticle->transitionTo('submit');

    $history = StateTransition::forModel($article)->get();

    expect($history)->toHaveCount(2);
    expect($history->pluck('transition')->toArray())->toBe(['submit', 'approve']);
});

it('calculates duration in previous state', function () {
    $article = createArticle();

    $article->transitionTo('submit');

    // Travel forward in time
    $this->travel(2)->hours();

    $article->transitionTo('approve');

    $lastTransition = StateTransition::latest()->first();
    $duration = $lastTransition->getDurationInPreviousState();

    expect($duration)->toBeInt();
    expect($duration)->toBe(7200); // 2 hours in seconds
});

it('can prune old history', function () {
    $article = createArticle();

    // Create old transition directly in the database
    DB::table('state_transitions')->insert([
        'model_type' => $article::class,
        'model_id' => $article->id,
        'property' => 'status',
        'transition' => 'archive',
        'from_state' => 'draft',
        'to_state' => 'published',
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDays(100),
    ]);

    // Create recent transition
    $article->transitionTo('submit');

    expect(StateTransition::count())->toBe(2);

    $repository = app(StateTransitionRepository::class);
    $pruned = $repository->prune(30);

    expect($pruned)->toBe(1);
    expect(StateTransition::count())->toBe(1);
});

it('respects state config history settings over global', function () {
    config(['status-machina.db_history_tracking.enabled' => false]);

    // Create config with history enabled
    $config = new class () extends AbstractStateConfig {
        public function __construct()
        {
            $this->initialState = 'start';
            $this->addStates(['start', 'end']);
            $this->setTransition(
                'go',
                $this->transition()->from('start')->to('end')
            );
            $this->trackHistory('database', ['enabled' => true]);
        }
    };

    StatusMachina::registerStateConfig('tracked', get_class($config));
    StatusMachina::registerStateManagement(Order::class, 'status', 'tracked');

    $order = createOrder(['status' => 'start']);
    $order->transitionTo('go');

    expect(StateTransition::count())->toBe(1);
});
