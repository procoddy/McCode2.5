<?php

declare(strict_types=1);

namespace App\Modules\Market\Repositories;

use App\Core\Database;
use InvalidArgumentException;

/**
 * item_market_listings and crystal_market_listings. Listings only shrink
 * through conditional updates, so two buyers can't take the same stock.
 */
class MarketRepository
{
    private const TABLES = ['item' => 'item_market_listings', 'crystal' => 'crystal_market_listings'];

    public function __construct(private readonly Database $db)
    {
    }

    public function itemListings(): array
    {
        return $this->db->fetchAll(
            'SELECT l.*, u.username AS seller, i.name AS item_name, t.name AS type_name
             FROM item_market_listings l
             JOIN users u ON u.id = l.seller_id
             JOIN items i ON i.id = l.item_id
             LEFT JOIN item_types t ON t.id = i.type_id
             ORDER BY t.name IS NULL, t.name, i.name, l.currency, l.price, l.id',
        );
    }

    public function crystalListings(): array
    {
        return $this->db->fetchAll(
            'SELECT l.*, u.username AS seller
             FROM crystal_market_listings l
             JOIN users u ON u.id = l.seller_id
             ORDER BY l.price_each, l.id',
        );
    }

    public function find(string $market, int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM {$this->table($market)} WHERE id = ?", [$id]);
    }

    public function create(string $market, array $data): int
    {
        return $this->db->insert($this->table($market), $data);
    }

    /** Take $quantity from a listing, deleting it when empty. False if fewer are left. */
    public function take(string $market, int $id, int $quantity): bool
    {
        $table = $this->table($market);
        $taken = $this->db->query(
            "UPDATE {$table} SET quantity = quantity - ? WHERE id = ? AND quantity >= ?",
            [$quantity, $id, $quantity],
        )->rowCount() === 1;

        if ($taken) {
            $this->db->query("DELETE FROM {$table} WHERE id = ? AND quantity <= 0", [$id]);
        }

        return $taken;
    }

    /** Delete the seller's own listing and return it, or null if it's gone or not theirs. */
    public function withdraw(string $market, int $id, int $sellerId): ?array
    {
        $table = $this->table($market);

        return $this->db->transaction(function () use ($table, $id, $sellerId): ?array {
            $row = $this->db->fetch("SELECT * FROM {$table} WHERE id = ? AND seller_id = ? FOR UPDATE", [$id, $sellerId]);

            if ($row !== null) {
                $this->db->delete($table, ['id' => $id]);
            }

            return $row;
        });
    }

    private function table(string $market): string
    {
        return self::TABLES[$market] ?? throw new InvalidArgumentException("Unknown market [{$market}].");
    }
}
