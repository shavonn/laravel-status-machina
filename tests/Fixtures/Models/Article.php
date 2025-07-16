<?php

namespace Shavonn\StatusMachina\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Shavonn\StatusMachina\Traits\HasStateMachine;

class Article extends Model
{
    use HasStateMachine;
    protected $fillable = ['title', 'content', 'status', 'is_valid', 'published_at', 'author_id'];
}
