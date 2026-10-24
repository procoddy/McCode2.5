<?php

declare(strict_types=1);

namespace App\Modules\Crimes\Models;

final class Crime
{
    public function __construct(
        public readonly int $id,
        public readonly int $groupId,
        public readonly string $name,
        public readonly int $braveCost,
        public readonly string $successFormula,
        public readonly int $successMoney = 0,
        public readonly int $successCrystals = 0,
        public readonly ?int $successItemId = null,
        public readonly int $crimeExp = 0,
        public readonly int $jailMinutes = 0,
        public readonly string $jailReason = '',
        public readonly string $initialText = '',
        public readonly string $successText = '',
        public readonly string $failureText = '',
        public readonly string $jailText = '',
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            groupId: (int) $row['group_id'],
            name: (string) $row['name'],
            braveCost: (int) $row['brave_cost'],
            successFormula: (string) $row['success_formula'],
            successMoney: (int) $row['success_money'],
            successCrystals: (int) $row['success_crystals'],
            successItemId: $row['success_item_id'] === null ? null : (int) $row['success_item_id'],
            crimeExp: (int) $row['crime_exp'],
            jailMinutes: (int) $row['jail_minutes'],
            jailReason: (string) $row['jail_reason'],
            initialText: (string) $row['initial_text'],
            successText: (string) $row['success_text'],
            failureText: (string) $row['failure_text'],
            jailText: (string) $row['jail_text'],
        );
    }
}
