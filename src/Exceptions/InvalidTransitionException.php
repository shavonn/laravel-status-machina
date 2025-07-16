<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Exceptions;

use Exception;

class InvalidTransitionException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly ?string $transition = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
