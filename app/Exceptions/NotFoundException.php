<?php

declare(strict_types=1);

namespace App\Exceptions;

class NotFoundException extends ApplicationException
{
    public function __construct(string $message = 'The page you are looking for could not be found.')
    {
        parent::__construct($message, 404);
    }
}
