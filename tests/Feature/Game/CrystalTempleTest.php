<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Support\Money;

final class CrystalTempleTest extends GameTestCase
{
    public function test_refill_energy(): void
    {
        $this->players->add(1, ['crystals' => 20, 'energy' => 2, 'maxEnergy' => 12]);

        $response = $this->post('/crystal-temple/refill', [], self::HTMX);

        $this->assertSame(200, $response->status());
        $this->assertSame(12, $this->player()->energy);
        $this->assertSame(8, $this->player()->crystals);
    }

    public function test_refill_refused_when_full_or_too_poor(): void
    {
        $this->players->add(1, ['crystals' => 20, 'energy' => 12, 'maxEnergy' => 12]);
        $this->assertStringContainsString('You already have full energy.', $this->post('/crystal-temple/refill', [], self::HTMX)->content());

        $this->players->add(1, ['crystals' => 3, 'energy' => 1, 'maxEnergy' => 12]);
        $this->assertSame(422, $this->post('/crystal-temple/refill', [], self::HTMX)->status());
        $this->assertSame(3, $this->player()->crystals);
    }

    public function test_trade_for_money_and_iq(): void
    {
        $this->players->add(1, ['crystals' => 10, 'money' => Money::of(0), 'iq' => 10]);

        $this->post('/crystal-temple/money', ['money_crystals' => '4'], self::HTMX);
        $this->post('/crystal-temple/iq', ['iq_crystals' => '2'], self::HTMX);

        $this->assertSame(800, $this->player()->money->amount());
        $this->assertSame(20.0, $this->player()->iq);
        $this->assertSame(4, $this->player()->crystals);
    }

    public function test_cannot_trade_more_than_owned(): void
    {
        $this->players->add(1, ['crystals' => 1]);

        $response = $this->post('/crystal-temple/money', ['money_crystals' => '5'], self::HTMX);

        $this->assertSame(422, $response->status());
        $this->assertSame(1, $this->player()->crystals);
    }
}
