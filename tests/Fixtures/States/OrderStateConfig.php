<?php

namespace Shavonn\StatusMachina\Tests\Fixtures\States;

use Shavonn\StatusMachina\Config\AbstractStateConfig;

class OrderStateConfig extends AbstractStateConfig
{
    protected string $initialState = 'pending';

    public function __construct()
    {
        $this->addStates([
            'pending',
            'processing',
            'shipped',
            'delivered',
            'cancelled'
        ]);

        $this->setTransition(
            'process',
            $this->transition()->from('pending')->to('processing')
        );

        $this->setTransition(
            'ship',
            $this->transition()->from('processing')->to('shipped')
        );

        $this->setTransition(
            'deliver',
            $this->transition()->from('shipped')->to('delivered')
        );

        $this->setTransition(
            'cancel',
            $this->transition()->from(['pending', 'processing'])->to('cancelled')
        );
    }
}
