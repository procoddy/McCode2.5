<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\ItemEffect;
use App\Modules\Players\Models\Player;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

final class ItemEffectTest extends TestCase
{
    private const NOW = '2026-10-25 12:00:00';

    public function test_figures_and_percentages(): void
    {
        $player = new Player(id: 1, username: 'a', money: Money::of(1000), maxEnergy: 40, strength: 50, jailUntil: '2026-10-25 13:00:00');

        $this->assertSame(25, (new ItemEffect('energy', true, false, 25))->delta($player, self::NOW));
        $this->assertSame(10, (new ItemEffect('energy', true, true, 25))->delta($player, self::NOW), '% of max energy');
        $this->assertSame(-100, (new ItemEffect('money', false, true, 10))->delta($player, self::NOW), '% of current money');
        $this->assertSame(5, (new ItemEffect('strength', true, true, 10))->delta($player, self::NOW));
        $this->assertSame(-30, (new ItemEffect('jail', false, true, 50))->delta($player, self::NOW), '% of remaining minutes');
        $this->assertSame(0, (new ItemEffect('hospital', false, true, 50))->delta($player, self::NOW), 'not in hospital');
    }

    public function test_reads_legacy_serialized_shape_and_json(): void
    {
        $legacy = ItemEffect::fromArray(['stat' => 'IQ', 'dir' => 'neg', 'inc_type' => 'percent', 'inc_amount' => '5']);
        $this->assertSame(['stat' => 'iq', 'direction' => 'decrease', 'type' => 'percent', 'amount' => 5], $legacy->toArray());

        $item = Item::fromRow([
            'id' => 1, 'name' => 'Potion', 'buy_price' => 1, 'sell_price' => 1, 'buyable' => 1, 'weapon' => 0, 'armor' => 0,
            'effects' => json_encode([['stat' => 'hp', 'direction' => 'increase', 'type' => 'figure', 'amount' => 50]]),
        ]);
        $this->assertTrue($item->isUsable());
        $this->assertSame('Increases health by 50', $item->effects[0]->describe());
    }

    public function test_rejects_unknown_stats(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ItemEffect('level', true, false, 1);
    }
}
