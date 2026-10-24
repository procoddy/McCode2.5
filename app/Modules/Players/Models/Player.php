<?php

declare(strict_types=1);

namespace App\Modules\Players\Models;

use App\Support\Money;

/**
 * Full game state of one player (replaces the legacy $ir array).
 */
final class Player
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly int $level = 1,
        public readonly int $exp = 0,
        public readonly int $crimeExp = 0,
        public readonly Money $money = new Money(0),
        public readonly int $crystals = 0,
        public readonly int $energy = 12,
        public readonly int $maxEnergy = 12,
        public readonly int $brave = 5,
        public readonly int $maxBrave = 5,
        public readonly int $will = 100,
        public readonly int $maxWill = 100,
        public readonly int $hp = 100,
        public readonly int $maxHp = 100,
        public readonly ?int $bankBalance = null,
        public readonly ?int $cyberBalance = null,
        public readonly ?string $jailUntil = null,
        public readonly ?string $jailReason = null,
        public readonly ?string $hospitalUntil = null,
        public readonly ?string $hospitalReason = null,
        public readonly float $strength = 10,
        public readonly float $agility = 10,
        public readonly float $guard = 10,
        public readonly float $labour = 10,
        public readonly float $iq = 10,
        public readonly ?int $cityId = 1,
        public readonly ?int $gangId = null,
        public readonly ?int $courseId = null,
        public readonly ?string $courseEndsAt = null,
        public readonly ?int $equippedPrimaryId = null,
        public readonly ?int $equippedSecondaryId = null,
        public readonly ?int $equippedArmorId = null,
        public readonly ?int $attackingUserId = null,
        public readonly string $gender = 'Male',
    ) {
    }

    public static function fromRow(array $row): self
    {
        $nullableInt = fn (string $key) => $row[$key] === null ? null : (int) $row[$key];

        return new self(
            id: (int) $row['id'],
            username: (string) $row['username'],
            level: (int) $row['level'],
            exp: (int) $row['exp'],
            crimeExp: (int) $row['crime_exp'],
            money: Money::of((int) $row['money']),
            crystals: (int) $row['crystals'],
            energy: (int) $row['energy'],
            maxEnergy: (int) $row['max_energy'],
            brave: (int) $row['brave'],
            maxBrave: (int) $row['max_brave'],
            will: (int) $row['will'],
            maxWill: (int) $row['max_will'],
            hp: (int) $row['hp'],
            maxHp: (int) $row['max_hp'],
            bankBalance: $nullableInt('bank_balance'),
            cyberBalance: $nullableInt('cyber_balance'),
            jailUntil: $row['jail_until'],
            jailReason: $row['jail_reason'],
            hospitalUntil: $row['hospital_until'],
            hospitalReason: $row['hospital_reason'],
            strength: (float) $row['strength'],
            agility: (float) $row['agility'],
            guard: (float) $row['guard'],
            labour: (float) $row['labour'],
            iq: (float) $row['iq'],
            cityId: $nullableInt('city_id'),
            gangId: $nullableInt('gang_id'),
            courseId: $nullableInt('course_id'),
            courseEndsAt: $row['course_ends_at'],
            equippedPrimaryId: $nullableInt('equipped_primary_id'),
            equippedSecondaryId: $nullableInt('equipped_secondary_id'),
            equippedArmorId: $nullableInt('equipped_armor_id'),
            attackingUserId: $nullableInt('attacking_user_id'),
            gender: (string) $row['gender'],
        );
    }

    /** EXP needed for the next level (legacy exp_needed). */
    public function expNeeded(): int
    {
        return self::expNeededFor($this->level);
    }

    public static function expNeededFor(int $level): int
    {
        return (int) (($level + 1) ** 3 * 2.2);
    }

    /** @param string $now 'Y-m-d H:i:s' */
    public function isJailed(string $now): bool
    {
        return $this->jailUntil !== null && $this->jailUntil > $now;
    }

    /** @param string $now 'Y-m-d H:i:s' */
    public function isHospitalized(string $now): bool
    {
        return $this->hospitalUntil !== null && $this->hospitalUntil > $now;
    }

    public function balance(string $column): ?int
    {
        return match ($column) {
            'bank_balance' => $this->bankBalance,
            'cyber_balance' => $this->cyberBalance,
            default => throw new \InvalidArgumentException("Unknown balance column [{$column}]."),
        };
    }
}
