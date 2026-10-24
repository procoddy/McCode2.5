<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Repositories;

use App\Core\Database;
use App\Modules\Inventory\Models\EquipmentSlot;
use App\Modules\Inventory\Models\InventoryEntry;
use App\Modules\Inventory\Models\Item;

/**
 * Items, inventories and equipment. Every removal is conditional on the
 * player still holding enough, so double submits can't duplicate items.
 */
class InventoryRepository
{
    private const ITEM_SELECT = 'SELECT i.*, t.name AS type_name FROM items i LEFT JOIN item_types t ON t.id = i.type_id';

    public function __construct(private readonly Database $db)
    {
    }

    public function findItem(int $id): ?Item
    {
        $row = $this->db->fetch(self::ITEM_SELECT . ' WHERE i.id = ?', [$id]);

        return $row === null ? null : Item::fromRow($row);
    }

    /** @return list<InventoryEntry> grouped by type, then by name */
    public function inventory(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT i.*, t.name AS type_name, inv.quantity
             FROM inventory inv
             JOIN items i ON i.id = inv.item_id
             LEFT JOIN item_types t ON t.id = i.type_id
             WHERE inv.user_id = ? AND inv.quantity > 0
             ORDER BY t.name IS NULL, t.name, i.name',
            [$userId],
        );

        return array_map(fn (array $row) => new InventoryEntry(Item::fromRow($row), (int) $row['quantity']), $rows);
    }

    public function quantity(int $userId, int $itemId): int
    {
        return (int) $this->db->fetchColumn('SELECT quantity FROM inventory WHERE user_id = ? AND item_id = ?', [$userId, $itemId]);
    }

    /** Legacy item_add(). */
    public function add(int $userId, int $itemId, int $quantity): void
    {
        $this->db->query(
            'INSERT INTO inventory (user_id, item_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)',
            [$userId, $itemId, $quantity],
        );
    }

    /** Legacy item_remove(). False (nothing removed) if the player has fewer than $quantity. */
    public function remove(int $userId, int $itemId, int $quantity): bool
    {
        $removed = $this->db->query(
            'UPDATE inventory SET quantity = quantity - ? WHERE user_id = ? AND item_id = ? AND quantity >= ?',
            [$quantity, $userId, $itemId, $quantity],
        )->rowCount() === 1;

        if ($removed) {
            $this->db->query('DELETE FROM inventory WHERE user_id = ? AND item_id = ? AND quantity <= 0', [$userId, $itemId]);
        }

        return $removed;
    }

    /** @return array<string, Item|null> slot value => equipped item */
    public function equipped(int $userId): array
    {
        $row = $this->db->fetch('SELECT equipped_primary_id, equipped_secondary_id, equipped_armor_id FROM users WHERE id = ?', [$userId]) ?? [];
        $equipped = [];

        foreach (EquipmentSlot::cases() as $slot) {
            $itemId = $row[$slot->column()] ?? null;
            $equipped[$slot->value] = $itemId === null ? null : $this->findItem((int) $itemId);
        }

        return $equipped;
    }

    /**
     * Move one $itemId from the inventory into $slot; whatever was in the slot
     * goes back to the inventory. False if the player doesn't hold the item.
     */
    public function equip(int $userId, EquipmentSlot $slot, int $itemId): bool
    {
        return $this->db->transaction(function () use ($userId, $slot, $itemId): bool {
            if (!$this->remove($userId, $itemId, 1)) {
                return false;
            }

            $column = $slot->column();
            $previous = $this->db->fetchColumn("SELECT {$column} FROM users WHERE id = ? FOR UPDATE", [$userId]);
            $this->db->query("UPDATE users SET {$column} = ? WHERE id = ?", [$itemId, $userId]);

            if ($previous !== null) {
                $this->add($userId, (int) $previous, 1);
            }

            return true;
        });
    }

    /** Legacy unequip.php. Returns the item that was in the slot, or null if it was empty. */
    public function unequip(int $userId, EquipmentSlot $slot): ?int
    {
        return $this->db->transaction(function () use ($userId, $slot): ?int {
            $column = $slot->column();
            $itemId = $this->db->fetchColumn("SELECT {$column} FROM users WHERE id = ? FOR UPDATE", [$userId]);

            if ($itemId === null) {
                return null;
            }

            $this->db->query("UPDATE users SET {$column} = NULL WHERE id = ?", [$userId]);
            $this->add($userId, (int) $itemId, 1);

            return (int) $itemId;
        });
    }

    /**
     * Use one item: remove it and apply the changes from ItemUseService. Bars
     * are capped at their maximum, nothing goes below zero, and accounts or
     * courses the player doesn't have are left alone.
     *
     * @param array<string, int> $deltas stat key (see ItemEffect::STATS) => signed change
     */
    public function useItem(int $userId, int $itemId, array $deltas, string $reason, string $now): bool
    {
        return $this->db->transaction(function () use ($userId, $itemId, $deltas, $reason, $now): bool {
            if (!$this->remove($userId, $itemId, 1)) {
                return false;
            }

            $userSets = [];
            $userParams = [];
            $statSets = [];
            $statParams = [];

            foreach ($deltas as $stat => $delta) {
                if ($delta === 0) {
                    continue;
                }

                switch ($stat) {
                    case 'energy': case 'will': case 'brave': case 'hp':
                        $userSets[] = "{$stat} = LEAST(max_{$stat}, GREATEST(0, {$stat} + ?))";
                        $userParams[] = $delta;
                        break;
                    case 'money': case 'crystals': case 'crime_exp':
                        $userSets[] = "{$stat} = GREATEST(0, {$stat} + ?)";
                        $userParams[] = $delta;
                        break;
                    case 'bank_balance': case 'cyber_balance':
                        $userSets[] = "{$stat} = IF({$stat} IS NULL, NULL, GREATEST(0, {$stat} + ?))";
                        $userParams[] = $delta;
                        break;
                    case 'hospital': case 'jail':
                        // The reason must be set first: later SET clauses see earlier ones' new values.
                        if ($delta > 0) {
                            $userSets[] = "{$stat}_reason = IF({$stat}_until > ?, {$stat}_reason, ?)";
                            $userSets[] = "{$stat}_until = GREATEST(COALESCE({$stat}_until, ?), ?) + INTERVAL ? MINUTE";
                            array_push($userParams, $now, $reason, $now, $now, $delta);
                        } else {
                            $userSets[] = "{$stat}_until = IF({$stat}_until IS NULL OR {$stat}_until + INTERVAL ? MINUTE <= ?, NULL, {$stat}_until + INTERVAL ? MINUTE)";
                            array_push($userParams, $delta, $now, $delta);
                        }
                        break;
                    case 'course_days':
                        $userSets[] = 'course_ends_at = IF(course_id IS NULL OR course_ends_at IS NULL, course_ends_at, GREATEST(?, course_ends_at + INTERVAL ? DAY))';
                        array_push($userParams, $now, $delta);
                        break;
                    case 'strength': case 'agility': case 'guard': case 'labour': case 'iq':
                        $statSets[] = "{$stat} = GREATEST(0, {$stat} + ?)";
                        $statParams[] = $delta;
                        break;
                }
            }

            if ($userSets !== []) {
                $this->db->query('UPDATE users SET ' . implode(', ', $userSets) . ' WHERE id = ?', [...$userParams, $userId]);
            }
            if ($statSets !== []) {
                $this->db->query('INSERT IGNORE INTO user_stats (user_id) VALUES (?)', [$userId]);
                $this->db->query('UPDATE user_stats SET ' . implode(', ', $statSets) . ' WHERE user_id = ?', [...$statParams, $userId]);
            }

            return true;
        });
    }

    /** @param array<string, mixed> $data columns of item_logs (created_at is filled in) */
    public function log(string $type, array $data, string $now): void
    {
        $this->db->insert('item_logs', ['type' => $type, ...$data, 'created_at' => $now]);
    }
}
