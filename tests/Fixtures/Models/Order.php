<?php

namespace Shavonn\StatusMachina\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Shavonn\StatusMachina\Traits\HasStateMachine;

class Order extends Model
{
    use HasStateMachine;
    protected $fillable = ['status', 'total', 'shipping_address'];
}
