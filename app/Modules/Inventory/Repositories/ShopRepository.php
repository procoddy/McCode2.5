<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Repositories;

use App\Core\Database;
use App\Modules\Inventory\Models\Item;

class ShopRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<array{id: int, name: string, description: ?string}> */
    public function inCity(?int $cityId): array
    {
        return $this->db->fetchAll('SELECT id, name, description FROM shops WHERE city_id = ? ORDER BY name', [$cityId ?? 0]);
    }

    /** @return array{id: int, city_id: int, name: string, description: ?string}|null */
    public function find(int $id): ?array
    {
        return $this->db->fetch('SELECT id, city_id, name, description FROM shops WHERE id = ?', [$id]);
    }

    /** @return list<Item> buyable stock, by type then price */
    public function stock(int $shopId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT i.*, t.name AS type_name
             FROM shop_items si
             JOIN items i ON i.id = si.item_id
             LEFT JOIN item_types t ON t.id = i.type_id
             WHERE si.shop_id = ? AND i.buyable = 1
             ORDER BY t.name IS NULL, t.name, i.buy_price, i.name',
            [$shopId],
        );

        return array_map(Item::fromRow(...), $rows);
    }

    public function sells(int $shopId, int $itemId): bool
    {
        return $this->db->fetchColumn('SELECT 1 FROM shop_items WHERE shop_id = ? AND item_id = ?', [$shopId, $itemId]) !== null;
    }
}
