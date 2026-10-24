<?php

declare(strict_types=1);

namespace Tests\Unit\Combat;

use App\Modules\Combat\Services\CombatCalculator;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FixedRandom;

final class CombatCalculatorTest extends TestCase
{
    public function test_hit_chance_is_clamped(): void
    {
        $calc = new CombatCalculator(new FixedRandom());

        $this->assertSame(60.0, $calc->hitChance(10, 10));
        $this->assertSame(95.0, $calc->hitChance(100, 10));
        $this->assertSame(10.0, $calc->hitChance(1, 100));
        $this->assertSame(95.0, $calc->hitChance(10, 0), 'zero agility does not divide by zero');
    }

    public function test_weapon_damage_with_armor_and_crits(): void
    {
        // power 10 * strength 15 / (guard 15 / 1.5) = 15, variance 1.0
        $this->assertSame(15, (new CombatCalculator(new FixedRandom([10000, 1])))->weaponDamage(10, 15, 15, 0));
        $this->assertSame(10, (new CombatCalculator(new FixedRandom([10000, 1])))->weaponDamage(10, 15, 15, 5), 'armor');
        $this->assertSame(1, (new CombatCalculator(new FixedRandom([10000, 1])))->weaponDamage(10, 15, 15, 500), 'at least 1');
        $this->assertSame(45, (new CombatCalculator(new FixedRandom([10000, 17, 30])))->weaponDamage(10, 15, 15, 0), 'critical x3');
        $this->assertSame(5, (new CombatCalculator(new FixedRandom([10000, 25, 30])))->weaponDamage(10, 15, 15, 0), 'glancing /3');
    }

    public function test_fists(): void
    {
        // (int)(1000 / 10 / 100) + 1 = 2
        $this->assertSame(2, (new CombatCalculator(new FixedRandom([10000, 1])))->fistDamage(1000, 10, 0));
    }
}
