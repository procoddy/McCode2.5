<?php

declare(strict_types=1);

namespace App\Modules\Combat\Models;

/**
 * What a fight needs to know about one side (legacy $youdata / $odata).
 */
final class Fighter
{
    /** @param array<int, array{name: string, power: int}> $weapons equipped weapons by item id */
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $gender,
        public readonly int $level,
        public readonly int $hp,
        public readonly int $maxHp,
        public readonly int $energy,
        public readonly int $maxEnergy,
        public readonly int $money,
        public readonly float $strength,
        public readonly float $agility,
        public readonly float $guard,
        public readonly int $armor,
        public readonly array $weapons,
        public readonly ?int $gangId,
        public readonly ?int $cityId,
        public readonly ?string $hospitalUntil,
        public readonly ?string $jailUntil,
        public readonly ?int $attackingUserId,
        public readonly bool $isNpc,
    ) {
    }

    public function pronoun(): string
    {
        return $this->gender === 'Female' ? 'her' : 'his';
    }

    public function isHospitalized(string $now): bool
    {
        return $this->hospitalUntil !== null && $this->hospitalUntil > $now;
    }

    public function isJailed(string $now): bool
    {
        return $this->jailUntil !== null && $this->jailUntil > $now;
    }

    public function attackCost(): int
    {
        return intdiv($this->maxEnergy, 2);
    }

    public function hpPercent(): int
    {
        return $this->maxHp > 0 ? (int) round(max(0, $this->hp) / $this->maxHp * 100) : 0;
    }
}
