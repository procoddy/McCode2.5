<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

/**
 * Legacy users.equip_primary / equip_secondary / equip_armor.
 */
enum EquipmentSlot: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Armor = 'armor';

    public function column(): string
    {
        return "equipped_{$this->value}_id";
    }

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Primary weapon',
            self::Secondary => 'Secondary weapon',
            self::Armor => 'Armor',
        };
    }

    public function accepts(Item $item): bool
    {
        return $this === self::Armor ? $item->armor > 0 : $item->weapon > 0;
    }
}
