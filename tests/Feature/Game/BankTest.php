<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Support\Money;

final class BankTest extends GameTestCase
{
    public function test_open_account_costs_money(): void
    {
        $this->players->add(1, ['username' => 'rocky', 'money' => Money::of(60_000)]);

        $response = $this->post('/bank/open', [], self::HTMX);

        $this->assertSame(200, $response->status());
        $this->assertSame(10_000, $this->player()->money->amount());
        $this->assertSame(0, $this->player()->bankBalance);
        $this->assertStringContainsString('id="bank-panel"', $response->content());
        // Top bar refreshed out of band with the new cash total.
        $this->assertMatchesRegularExpression('/id="player-status" hx-swap-oob="true"[\s\S]*\$10,000/', $response->content());
    }

    public function test_cannot_open_without_enough_money(): void
    {
        $this->players->add(1, ['money' => Money::of(100)]);

        $response = $this->post('/bank/open', [], self::HTMX);

        $this->assertSame(422, $response->status());
        $this->assertStringContainsString('You need $50,000 to open an account.', $response->content());
        $this->assertNull($this->player()->bankBalance);
    }

    public function test_deposit_charges_capped_fee(): void
    {
        $this->players->add(1, ['money' => Money::of(100_000), 'bankBalance' => 0]);

        $this->post('/bank/deposit', ['deposit' => '100000'], self::HTMX);

        $this->assertSame(0, $this->player()->money->amount());
        $this->assertSame(97_000, $this->player()->bankBalance); // fee capped at 3,000
    }

    public function test_cannot_deposit_more_than_cash(): void
    {
        $this->players->add(1, ['money' => Money::of(50), 'bankBalance' => 0]);

        $response = $this->post('/bank/deposit', ['deposit' => '51'], self::HTMX);

        $this->assertSame(422, $response->status());
        $this->assertStringContainsString('You do not have enough money to deposit this amount.', $response->content());
        $this->assertStringContainsString('value="51"', $response->content(), 'submitted amount is kept');
        $this->assertSame(50, $this->player()->money->amount());
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $this->players->add(1, ['money' => Money::of(50), 'bankBalance' => 0]);

        foreach (['-5', '0', 'abc', '1.5'] as $amount) {
            $this->assertSame(422, $this->post('/bank/deposit', ['deposit' => $amount], self::HTMX)->status(), $amount);
        }
        $this->assertSame(50, $this->player()->money->amount());
    }

    public function test_cyber_bank_withdraw_takes_full_amount_from_balance(): void
    {
        $this->players->add(1, ['money' => Money::of(0), 'cyberBalance' => 10_000]);

        $this->post('/cyber-bank/withdraw', ['withdraw' => '10000'], self::HTMX);

        $this->assertSame(0, $this->player()->cyberBalance);
        $this->assertSame(9_250, $this->player()->money->amount()); // 7.5% fee
    }

    public function test_without_htmx_redirects_with_flash(): void
    {
        $this->players->add(1, ['money' => Money::of(1_000), 'bankBalance' => 0]);

        $response = $this->post('/bank/deposit', ['deposit' => '1000']);

        $this->assertSame('/bank', $response->getHeader('Location'));
        $this->assertStringContainsString('You deposited $1,000', $this->get('/bank')->content());
    }

    public function test_page_shows_open_offer_or_balance(): void
    {
        $this->players->add(1, ['bankBalance' => null]);
        $this->assertStringContainsString('Yes, sign me up', $this->get('/bank')->content());

        $this->players->add(1, ['bankBalance' => 1_234]);
        $this->assertStringContainsString('$1,234', $this->get('/bank')->content());
    }
}
