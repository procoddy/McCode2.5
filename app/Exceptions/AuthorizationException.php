<?php

declare(strict_types=1);

namespace App\Exceptions;

class AuthorizationException extends ApplicationException
{
    public function __construct(string $message = 'You are not allowed to do that.')
    {
        parent::__construct($message, 403);
    }
}
