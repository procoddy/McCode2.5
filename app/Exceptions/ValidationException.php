<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends ApplicationException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(private readonly array $errors, string $message = 'The given data was invalid.')
    {
        parent::__construct($message, 422);
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public static function withMessages(array $messages): self
    {
        return new self(array_map(fn ($message) => (array) $message, $messages));
    }
}
