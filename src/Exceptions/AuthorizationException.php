<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\Exceptions;

use Exception;

class AuthorizationException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 403,
        ?Exception $previous = null,
        public readonly ?string $ability = null,
        public readonly ?object $user = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
