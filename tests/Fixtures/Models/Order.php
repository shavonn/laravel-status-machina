<?php

namespace SysMatter\StatusMachina\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use SysMatter\StatusMachina\Traits\HasStateMachine;

class Order extends Model
{
    use HasStateMachine;
    protected $fillable = ['status', 'total', 'shipping_address'];
}
