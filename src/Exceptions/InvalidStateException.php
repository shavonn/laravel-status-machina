<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\Exceptions;

use Exception;

class InvalidStateException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        public readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }
}
