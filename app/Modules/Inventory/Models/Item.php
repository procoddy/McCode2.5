<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Support\Money;

final class Item
{
    /** @param list<ItemEffect> $effects */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description = '',
        public readonly ?string $typeName = null,
        public readonly int $buyPrice = 0,
        public readonly int $sellPrice = 0,
        public readonly bool $buyable = false,
        public readonly array $effects = [],
        public readonly int $weapon = 0,
        public readonly int $armor = 0,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            description: (string) ($row['description'] ?? ''),
            typeName: $row['type_name'] ?? null,
            buyPrice: (int) $row['buy_price'],
            sellPrice: (int) $row['sell_price'],
            buyable: (bool) $row['buyable'],
            effects: self::decodeEffects($row['effects'] ?? null),
            weapon: (int) $row['weapon'],
            armor: (int) $row['armor'],
        );
    }

    /** @return list<ItemEffect> */
    public static function decodeEffects(?string $json): array
    {
        $list = $json === null || $json === '' ? [] : json_decode($json, true);

        return is_array($list) ? array_map(ItemEffect::fromArray(...), array_values($list)) : [];
    }

    public function isUsable(): bool
    {
        return $this->effects !== [];
    }

    public function isWeapon(): bool
    {
        return $this->weapon > 0;
    }

    public function isArmor(): bool
    {
        return $this->armor > 0;
    }

    public function sellValue(int $quantity = 1): Money
    {
        return Money::of($this->sellPrice * $quantity);
    }
}
