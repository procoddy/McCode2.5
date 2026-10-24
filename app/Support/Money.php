<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Immutable whole-unit amount of in-game currency. Integers avoid float
 * rounding errors in banking and market transactions.
 */
final class Money
{
    public function __construct(private readonly int $amount)
    {
    }

    public static function of(int $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function add(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function subtract(self $other): self
    {
        return new self($this->amount - $other->amount);
    }

    public function multiply(int|float $factor): self
    {
        return new self((int) round($this->amount * $factor));
    }

    /** Percentage of this amount, rounded down (e.g. bank interest, fees). */
    public function percentage(int|float $percent): self
    {
        if ($percent < 0) {
            throw new InvalidArgumentException('Percentage cannot be negative.');
        }

        return new self((int) floor($this->amount * $percent / 100));
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->amount >= $other->amount;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function format(string $symbol = '$'): string
    {
        return ($this->amount < 0 ? '-' : '') . $symbol . number_format(abs($this->amount));
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
