<?php

declare(strict_types=1);

namespace App\Modules\Combat\Services;

use App\Contracts\RandomInterface;

/**
 * The legacy attack.php formulas, without the database.
 */
final class CombatCalculator
{
    public function __construct(private readonly RandomInterface $random)
    {
    }

    /** Percent chance to hit: 60% at equal agility, between 10 and 95. */
    public function hitChance(float $attackerAgility, float $defenderAgility): float
    {
        return max(10.0, min(60 * $attackerAgility / max($defenderAgility, 0.01), 95.0));
    }

    public function hits(float $attackerAgility, float $defenderAgility): bool
    {
        return $this->random->int(1, 100) <= $this->hitChance($attackerAgility, $defenderAgility);
    }

    /** Damage of a hit with a weapon of $power, after the defender's armor and a possible critical. */
    public function weaponDamage(int $power, float $strength, float $defenderGuard, int $defenderArmor): int
    {
        $damage = (int) (($power * $strength / (max($defenderGuard, 0.01) / 1.5)) * $this->variance());

        return $this->finish($damage - $defenderArmor);
    }

    /** Legacy: defenders with no weapon hit back with their fists. */
    public function fistDamage(float $strength, float $defenderGuard, int $defenderArmor): int
    {
        $damage = (int) (((int) ($strength / max($defenderGuard, 0.01) / 100) + 1) * $this->variance());

        return $this->finish($damage - $defenderArmor);
    }

    /** @param list<int> $ids */
    public function pick(array $ids): int
    {
        return $ids[$this->random->int(0, count($ids) - 1)];
    }

    private function variance(): float
    {
        return $this->random->int(8000, 12000) / 10000;
    }

    /** At least 1; 1 in 40 hits triples-ish, 2 in 40 are softened. */
    private function finish(int $damage): int
    {
        $damage = (float) max(1, $damage);
        $crit = $this->random->int(1, 40);

        if ($crit === 17) {
            $damage *= $this->random->int(20, 40) / 10;
        } elseif ($crit === 25 || $crit === 8) {
            $damage /= $this->random->int(20, 40) / 10;
        }

        return max(1, (int) round($damage));
    }
}
