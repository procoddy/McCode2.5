<?php

declare(strict_types=1);

namespace Tests\Feature\Casino;

use App\Contracts\RandomInterface;
use App\Modules\Casino\Services\SlotsService;
use App\Support\Money;
use Tests\Fakes\FixedRandom;
use Tests\TestCase;

final class SlotsTest extends TestCase
{
    private function spin(array $reels, int $money = 1000, int $bet = 100): array
    {
        $this->app->container()->instance(RandomInterface::class, new FixedRandom($reels));
        $this->players->add(1, ['money' => Money::of($money), 'level' => 1]);

        return $this->app->container()->make(SlotsService::class)->spin($this->players->find(1), $bet);
    }

    public function test_three_of_a_kind_pays_26x(): void
    {
        $this->assertSame(2600, $this->spin([7, 7, 7])['won']);
        $this->assertSame(3500, $this->players->find(1)->money->amount());
    }

    public function test_two_of_a_kind_pays_3x_and_miss_loses_bet(): void
    {
        $this->assertSame(300, $this->spin([1, 2, 1])['won']);
        $this->assertSame(1200, $this->players->find(1)->money->amount());

        $this->assertSame(0, $this->spin([1, 2, 3])['won']);
        $this->assertSame(900, $this->players->find(1)->money->amount());
    }

    public function test_limits(): void
    {
        $this->expectException(\App\Exceptions\ValidationException::class);
        $this->spin([1, 1, 1], 1000, 251);
    }

    public function test_cannot_bet_more_than_you_have(): void
    {
        $this->expectException(\App\Exceptions\ValidationException::class);
        $this->spin([1, 1, 1], 50, 100);
    }
}
