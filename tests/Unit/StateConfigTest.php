<?php

use SysMatter\StatusMachina\Config\AbstractStateConfig;

it('includes instantiated state automatically', function () {
    $config = new class () extends AbstractStateConfig {
        protected string $initialState = 'draft';
        protected array $states = ['draft', 'published'];
    };

    expect($config->states())->toContain('instantiated', 'draft', 'published');
});

it('includes init transition automatically', function () {
    $config = new class () extends AbstractStateConfig {
        protected string $initialState = 'draft';
    };

    $transitions = $config->transitions();

    expect($transitions)->toHaveKey('init');
    expect($transitions['init']->getFrom())->toBe('instantiated');
    expect($transitions['init']->getTo())->toBe('draft');
});

it('prevents modification after finalization', function () {
    $config = new class () extends AbstractStateConfig {
        protected string $initialState = 'draft';

        public function addStatePublic(string $state): void
        {
            $this->state($state);
        }
    };

    $config->finalize();

    expect(fn () => $config->addStatePublic('new'))
        ->toThrow(LogicException::class, 'Cannot modify finalized state configuration');
});
