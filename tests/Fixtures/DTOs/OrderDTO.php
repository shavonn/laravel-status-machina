<?php

namespace SysMatter\StatusMachina\Tests\Fixtures\DTOs;

class OrderDTO
{
    public string $status = '';
    public array $items = [];
    public float $total = 0.0;
}
