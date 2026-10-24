<?php

declare(strict_types=1);

namespace Tests\Unit\Banking;

use App\Modules\Banking\Models\BankAccountType;
use PHPUnit\Framework\TestCase;

final class BankAccountTypeTest extends TestCase
{
    public function test_fees_round_up_and_are_capped(): void
    {
        $bank = new BankAccountType('bank', 'Bank', '/bank', 'bank_balance', 50_000, 15, 3_000, 0, 0, 2);

        $this->assertSame(2, $bank->depositFee(10));        // 1.5 rounds up
        $this->assertSame(150, $bank->depositFee(1_000));
        $this->assertSame(3_000, $bank->depositFee(1_000_000)); // capped
        $this->assertSame(0, $bank->withdrawFee(1_000_000));
    }
}
