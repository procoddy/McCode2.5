<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_arithmetic_is_immutable(): void
    {
        $wallet = Money::of(1000);
        $after = $wallet->subtract(Money::of(250));

        $this->assertSame(1000, $wallet->amount());
        $this->assertSame(750, $after->amount());
    }

    public function test_percentage_rounds_down(): void
    {
        $this->assertSame(33, Money::of(1111)->percentage(3)->amount());
    }

    public function test_format(): void
    {
        $this->assertSame('$1,234,567', Money::of(1234567)->format());
        $this->assertSame('-$50', Money::of(-50)->format());
    }
}
