<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

/** One inventory row: an item and how many the player holds. */
final class InventoryEntry
{
    public function __construct(public readonly Item $item, public readonly int $quantity)
    {
    }
}
