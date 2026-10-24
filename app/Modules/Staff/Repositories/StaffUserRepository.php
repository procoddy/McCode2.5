<?php

declare(strict_types=1);

namespace App\Modules\Staff\Repositories;

use App\Core\Database;
use App\Support\Pagination;

/**
 * Staff-side player management (legacy staff_users.php, staffnotes.php, staff_punit.php).
 */
class StaffUserRepository
{
    public const STAT_COLUMNS = ['strength', 'agility', 'guard', 'labour', 'iq'];

    public function __construct(private readonly Database $db)
    {
    }

    public function search(string $query, int $page): Pagination
    {
        [$where, $params] = $this->searchCondition($query);
        $pagination = new Pagination((int) $this->db->fetchColumn("SELECT COUNT(*) FROM users u {$where}", $params), 50, $page);

        return $pagination->withItems($this->db->fetchAll(
            "SELECT u.id, u.username, u.email, u.level, u.money, u.role, u.account_type, u.last_seen_at
             FROM users u {$where} ORDER BY u.id LIMIT {$pagination->perPage} OFFSET {$pagination->offset()}",
            $params,
        ));
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT u.*, COALESCE(s.strength, 10) AS strength, COALESCE(s.agility, 10) AS agility,
                    COALESCE(s.guard, 10) AS guard, COALESCE(s.labour, 10) AS labour, COALESCE(s.iq, 10) AS iq
             FROM users u LEFT JOIN user_stats s ON s.user_id = u.id WHERE u.id = ?',
            [$id],
        );
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetch('SELECT id, username FROM users WHERE username = ?', [$username]);
    }

    public function valueTaken(string $column, string $value, int $exceptId): bool
    {
        return $this->db->fetchColumn(
            "SELECT 1 FROM users WHERE {$this->db->quoteIdentifier($column)} = ? AND id <> ?",
            [$value, $exceptId],
        ) !== null;
    }

    public function update(int $id, array $user, array $stats): void
    {
        $this->db->transaction(function () use ($id, $user, $stats): void {
            $this->db->update('users', $user, ['id' => $id]);
            $this->db->query(
                'INSERT INTO user_stats (user_id, strength, agility, guard, labour, iq) VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE strength = VALUES(strength), agility = VALUES(agility), guard = VALUES(guard),
                 labour = VALUES(labour), iq = VALUES(iq)',
                [$id, ...array_map(fn ($c) => $stats[$c], self::STAT_COLUMNS)],
            );
        });
    }

    public function credit(int $id, int $money, int $crystals): void
    {
        $this->db->query('UPDATE users SET money = money + ?, crystals = crystals + ? WHERE id = ?', [$money, $crystals, $id]);
    }

    public function creditAll(int $money, int $crystals): int
    {
        return $this->db->query('UPDATE users SET money = money + ?, crystals = crystals + ?', [$money, $crystals])->rowCount();
    }

    public function forceLogout(int $id): void
    {
        $this->db->update('users', ['force_logout' => 1], ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('users', ['id' => $id]);
    }

    public function updateNotes(int $id, string $notes, int $changedBy, string $now): void
    {
        $this->db->transaction(function () use ($id, $notes, $changedBy, $now): void {
            $old = $this->db->fetchColumn('SELECT staff_notes FROM users WHERE id = ?', [$id]);
            $this->db->update('users', ['staff_notes' => $notes], ['id' => $id]);
            $this->db->insert('staff_note_logs', [
                'changed_by_id' => $changedBy,
                'user_id' => $id,
                'old_notes' => $old,
                'new_notes' => $notes,
                'created_at' => $now,
            ]);
        });
    }

    /** @return list<array<string, mixed>> */
    public function reports(): array
    {
        return $this->db->fetchAll(
            'SELECT r.id, r.body, r.created_at, r.reporter_id, r.reported_id,
                    u1.username AS reporter, u2.username AS reported
             FROM player_reports r
             LEFT JOIN users u1 ON u1.id = r.reporter_id
             LEFT JOIN users u2 ON u2.id = r.reported_id
             ORDER BY r.id DESC',
        );
    }

    public function deleteReport(int $id): bool
    {
        return $this->db->delete('player_reports', ['id' => $id]) > 0;
    }

    /** @return list<array<string, mixed>> */
    public function federalJail(): array
    {
        return $this->db->fetchAll(
            'SELECT f.user_id, f.jailed_until, f.reason, u.username, s.username AS jailed_by
             FROM federal_jail f
             JOIN users u ON u.id = f.user_id
             LEFT JOIN users s ON s.id = f.jailed_by_id
             ORDER BY f.jailed_until',
        );
    }

    public function jail(int $userId, int $staffId, int $days, string $reason, string $until, string $now): void
    {
        $this->db->transaction(function () use ($userId, $staffId, $days, $reason, $until, $now): void {
            $this->db->query(
                'INSERT INTO federal_jail (user_id, jailed_until, jailed_by_id, reason) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE jailed_until = VALUES(jailed_until), jailed_by_id = VALUES(jailed_by_id), reason = VALUES(reason)',
                [$userId, $until, $staffId, $reason],
            );
            $this->db->insert('federal_jail_logs', [
                'action' => 'jail', 'staff_id' => $staffId, 'user_id' => $userId,
                'days' => $days, 'reason' => $reason, 'created_at' => $now,
            ]);
        });
    }

    public function release(int $userId, int $staffId, string $now): bool
    {
        return $this->db->transaction(function () use ($userId, $staffId, $now): bool {
            if ($this->db->delete('federal_jail', ['user_id' => $userId]) === 0) {
                return false;
            }
            $this->db->insert('federal_jail_logs', [
                'action' => 'release', 'staff_id' => $staffId, 'user_id' => $userId, 'created_at' => $now,
            ]);

            return true;
        });
    }

    /** @return array{0: string, 1: list<mixed>} */
    private function searchCondition(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return ['', []];
        }
        if (ctype_digit($query)) {
            return ['WHERE u.id = ?', [(int) $query]];
        }

        $like = '%' . addcslashes($query, '%_\\') . '%';

        return ['WHERE u.username LIKE ? OR u.email LIKE ?', [$like, $like]];
    }
}
