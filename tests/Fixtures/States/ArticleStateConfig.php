<?php

namespace SysMatter\StatusMachina\Tests\Fixtures\States;

use Exception;
use SysMatter\StatusMachina\Config\AbstractStateConfig;

class ArticleStateConfig extends AbstractStateConfig
{
    protected string $initialState = 'draft';

    public function __construct()
    {
        $this->addStates([
            'draft',
            'pending_review',
            'approved',
            'changes_requested',
            'rejected',
            'published',
            'archived'
        ]);

        $this->setTransition(
            'submit',
            $this->transition()->from('draft')->to('pending_review')
        );

        $this->setTransition(
            'approve',
            $this->transition()->from('pending_review')->to('approved')
        );

        $this->setTransition(
            'reject',
            $this->transition()->from('pending_review')->to('rejected')
        );

        $this->setTransition(
            'request_changes',
            $this->transition()->from('pending_review')->to('changes_requested')
        );

        $this->setTransition(
            'publish',
            $this->transition()->from(['approved', 'archived'])->to('published')
        );

        $this->setTransition(
            'archive',
            $this->transition()->from('*')->to('archived')
        );

        // Hook that checks is_valid ONLY for submit transition
        $this->beforeTransition('submit', function ($article) {
            if (!$article->is_valid) {
                throw new Exception('Article must be valid');
            }
        });

        // Authorization hooks
        $this->protectTransition('approve', 'review');
        $this->protectTransition('publish', 'publish');
    }
}
