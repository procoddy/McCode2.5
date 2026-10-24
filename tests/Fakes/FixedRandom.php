<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\RandomInterface;

/** Returns queued values in order; fails loudly if a test didn't queue enough. */
final class FixedRandom implements RandomInterface
{
    /** @param list<int> $values */
    public function __construct(private array $values = [])
    {
    }

    public function push(int ...$values): void
    {
        array_push($this->values, ...$values);
    }

    public function int(int $min, int $max): int
    {
        if ($this->values === []) {
            throw new \LogicException("FixedRandom ran out of values (asked for {$min}-{$max}).");
        }

        return array_shift($this->values);
    }
}
