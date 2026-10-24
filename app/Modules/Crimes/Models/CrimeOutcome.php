<?php

declare(strict_types=1);

namespace App\Modules\Crimes\Models;

final class CrimeOutcome
{
    public const SUCCESS = 'success';
    public const FAILURE = 'failure';
    public const JAILED = 'jailed';

    public function __construct(
        public readonly Crime $crime,
        public readonly string $result,
        public readonly string $text,
        public readonly ?string $jailedUntil = null,
    ) {
    }

    public function succeeded(): bool
    {
        return $this->result === self::SUCCESS;
    }
}
