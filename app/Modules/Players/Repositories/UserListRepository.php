<?php

declare(strict_types=1);

namespace App\Modules\Players\Repositories;

use App\Core\Database;

/**
 * user_lists: legacy friendslist (friend) and blacklist (enemy).
 */
class UserListRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function entries(int $ownerId, string $list): array
    {
        return $this->db->fetchAll(
            'SELECT l.id, l.comment, u.id AS user_id, u.username, u.last_seen_at, u.donator_until
             FROM user_lists l
             JOIN users u ON u.id = l.target_id
             WHERE l.owner_id = ? AND l.list = ?
             ORDER BY u.username',
            [$ownerId, $list],
        );
    }

    /** False if already on the list. */
    public function add(int $ownerId, int $targetId, string $list, string $comment, string $now): bool
    {
        return $this->db->query(
            'INSERT IGNORE INTO user_lists (owner_id, target_id, list, comment, created_at) VALUES (?, ?, ?, ?, ?)',
            [$ownerId, $targetId, $list, $comment, $now],
        )->rowCount() === 1;
    }

    public function remove(int $id, int $ownerId, string $list): bool
    {
        return $this->db->delete('user_lists', ['id' => $id, 'owner_id' => $ownerId, 'list' => $list]) === 1;
    }

    /** False if the entry isn't the owner's. */
    public function comment(int $id, int $ownerId, string $list, string $comment): bool
    {
        if ($this->db->fetchColumn('SELECT 1 FROM user_lists WHERE id = ? AND owner_id = ? AND list = ?', [$id, $ownerId, $list]) === null) {
            return false;
        }

        $this->db->update('user_lists', ['comment' => $comment], ['id' => $id]);

        return true;
    }

    /** How many players have $userId on their list. */
    public function listedBy(int $userId, string $list): int
    {
        return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM user_lists WHERE target_id = ? AND list = ?', [$userId, $list]);
    }

    /** @return list<array{id: int, username: string, total: int}> */
    public function mostListed(string $list, int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, COUNT(*) AS total
             FROM user_lists l JOIN users u ON u.id = l.target_id
             WHERE l.list = ?
             GROUP BY u.id, u.username
             ORDER BY total DESC, u.username
             LIMIT {$limit}",
            [$list],
        );
    }
}
