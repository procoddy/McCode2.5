<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Source of randomness for game outcomes, so tests can make them predictable.
 */
interface RandomInterface
{
    /** Random integer between $min and $max inclusive. */
    public function int(int $min, int $max): int;
}
