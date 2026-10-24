<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Contracts\ClockInterface;
use App\Contracts\RandomInterface;
use App\Modules\Crimes\Contracts\CrimeRepositoryInterface;
use App\Modules\Crimes\Models\Crime;
use App\Support\Money;
use DateTimeImmutable;
use Tests\Fakes\FixedRandom;
use Tests\Fakes\InMemoryCrimeRepository;

final class CrimeTest extends GameTestCase
{
    private FixedRandom $random;

    protected function setUp(): void
    {
        parent::setUp();

        $crimes = new InMemoryCrimeRepository();
        $crimes->add(new Crime(
            id: 1, groupId: 1, name: 'Shoplift', braveCost: 3, successFormula: 'WILL / 2',
            successMoney: 400, successCrystals: 1, crimeExp: 4, jailMinutes: 10, jailReason: 'Shoplifting',
            initialText: 'You walk in.', successText: 'You got <b>{money}</b>.', failureText: 'No luck.', jailText: 'Busted.',
        ));
        $this->random = new FixedRandom();
        $clock = new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-10-25 12:00:00');
            }
        };

        $this->app->container()->instance(CrimeRepositoryInterface::class, $crimes);
        $this->app->container()->instance(RandomInterface::class, $this->random);
        $this->app->container()->instance(ClockInterface::class, $clock);
        $this->players->add(1, ['brave' => 5, 'will' => 100, 'money' => Money::of(0)]);
    }

    public function test_list_shows_chance_from_formula(): void
    {
        $this->assertStringContainsString('3 brave · 50% chance', $this->get('/crimes')->content());
    }

    public function test_success_rewards_and_escapes_staff_text(): void
    {
        $this->random->push(50); // roll <= 50% chance

        $response = $this->post('/crimes/1', [], self::HTMX);

        $player = $this->player();
        $this->assertSame([2, 400, 1, 50, 4], [$player->brave, $player->money->amount(), $player->crystals, $player->exp, $player->crimeExp]);
        $this->assertStringContainsString('You got &lt;b&gt;$400&lt;/b&gt;.', $response->content());
        $this->assertStringContainsString('hx-swap-oob="true"', $response->content());
    }

    public function test_failure_only_costs_brave(): void
    {
        $this->random->push(51, 1);

        $this->assertStringContainsString('No luck.', $this->post('/crimes/1', [], self::HTMX)->content());
        $this->assertSame([2, 0], [$this->player()->brave, $this->player()->money->amount()]);
    }

    public function test_jail_blocks_further_crimes(): void
    {
        $this->random->push(51, 2);

        $this->assertStringContainsString('Busted.', $this->post('/crimes/1', [], self::HTMX)->content());
        $this->assertSame('2026-10-25 12:10:00', $this->player()->jailUntil);

        $response = $this->post('/crimes/1', [], self::HTMX);
        $this->assertSame(422, $response->status());
        $this->assertStringContainsString('You are in jail until 12:10 pm.', $response->content());
        $this->assertStringNotContainsString('hx-post="/crimes/1"', $this->get('/crimes')->content());
    }

    public function test_not_enough_brave(): void
    {
        $this->players->add(1, ['brave' => 2]);

        $response = $this->post('/crimes/1', [], self::HTMX);

        $this->assertSame(422, $response->status());
        $this->assertStringContainsString('You do not have enough brave', $response->content());
    }

    public function test_unknown_crime_is_404(): void
    {
        $this->assertSame(404, $this->post('/crimes/99', [], self::HTMX)->status());
    }
}
