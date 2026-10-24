<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\RandomInterface;

final class SystemRandom implements RandomInterface
{
    public function int(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
