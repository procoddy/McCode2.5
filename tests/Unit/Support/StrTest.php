<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function test_slug(): void
    {
        $this->assertSame('grand-theft-auto', Str::slug('  Grand Theft -- Auto! '));
    }

    public function test_case_conversions(): void
    {
        $this->assertSame('BankAccount', Str::studly('bank_account'));
        $this->assertSame('bankAccount', Str::camel('bank-account'));
        $this->assertSame('bank_account', Str::snake('BankAccount'));
    }

    public function test_plural(): void
    {
        $this->assertSame('items', Str::plural('item'));
        $this->assertSame('categories', Str::plural('category'));
        $this->assertSame('keys', Str::plural('key'));
        $this->assertSame('boxes', Str::plural('box'));
        $this->assertSame('bank_accounts', Str::plural('bank_account'));
        $this->assertSame('people', Str::plural('person'));
    }

    public function test_random_length(): void
    {
        $this->assertSame(24, strlen(Str::random(24)));
    }
}
