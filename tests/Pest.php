<?php

use SysMatter\StatusMachina\Tests\TestCase;
use SysMatter\StatusMachina\StatusMachina;
use SysMatter\StatusMachina\Tests\Fixtures\States\ArticleStateConfig;
use SysMatter\StatusMachina\Tests\Fixtures\States\OrderStateConfig;
use SysMatter\StatusMachina\Tests\Fixtures\Models\Article;
use SysMatter\StatusMachina\Tests\Fixtures\Models\Order;

uses(TestCase::class)->in('Feature', 'Unit');

// Global helpers
function createArticle(array $attributes = []): Article
{
    return Article::create(array_merge([
        'title' => 'Test Article',
        'content' => 'Test content',
        'status' => 'draft',
        'is_valid' => true,
    ], $attributes));
}

function createOrder(array $attributes = []): Order
{
    return Order::create(array_merge([
        'total' => 99.99,
        'status' => 'pending',
    ], $attributes));
}

// This runs before each test in all files
uses()->beforeEach(function () {
    // Reset the manager state
    $manager = app('status-machina');
    $reflection = new ReflectionClass($manager);

    $stateConfigs = $reflection->getProperty('stateConfigs');
    $stateConfigs->setAccessible(true);
    $stateConfigs->setValue($manager, []);

    $stateManagement = $reflection->getProperty('stateManagement');
    $stateManagement->setAccessible(true);
    $stateManagement->setValue($manager, []);

    // Register default configs
    StatusMachina::registerStateConfig('article', ArticleStateConfig::class);
    StatusMachina::registerStateManagement(Article::class, 'status', 'article');

    StatusMachina::registerStateConfig('order', OrderStateConfig::class);
    StatusMachina::registerStateManagement(Order::class, 'status', 'order');
})->in('Feature');
