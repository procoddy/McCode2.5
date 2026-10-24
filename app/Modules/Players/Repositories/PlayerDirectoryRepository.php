<?php

declare(strict_types=1);

namespace App\Modules\Players\Repositories;

use App\Core\Database;
use App\Support\Pagination;

/**
 * Read-only queries for public player pages (legacy userlist.php,
 * usersonline.php, stafflist.php, stats.php, viewuser.php).
 */
class PlayerDirectoryRepository
{
    public const SORTABLE = ['id', 'username', 'level', 'money'];

    public function __construct(private readonly Database $db)
    {
    }

    public function paginate(string $sort, string $direction, int $page, int $perPage = 100): Pagination
    {
        $sort = in_array($sort, self::SORTABLE, true) ? $sort : 'id';
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $pagination = new Pagination((int) $this->db->fetchColumn('SELECT COUNT(*) FROM users'), $perPage, $page);

        return $pagination->withItems($this->db->fetchAll(
            "SELECT u.id, u.username, u.level, u.money, u.gender, u.account_type, u.last_seen_at, u.donator_until, g.prefix AS gang_prefix
             FROM users u LEFT JOIN gangs g ON g.id = u.gang_id
             ORDER BY u.{$sort} {$direction}, u.id LIMIT {$pagination->perPage} OFFSET {$pagination->offset()}",
        ));
    }

    /** @return list<array<string, mixed>> */
    public function onlineSince(string $since): array
    {
        return $this->db->fetchAll(
            'SELECT id, username, last_seen_at FROM users WHERE last_seen_at > ? ORDER BY last_seen_at DESC',
            [$since],
        );
    }

    /** @return list<array<string, mixed>> Role holders and owners. */
    public function staff(): array
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.level, u.last_seen_at,
                    TRIM(BOTH ', ' FROM CONCAT(IF(u.role = 'admin', 'Owner, ', ''), COALESCE(GROUP_CONCAT(r.name ORDER BY r.id SEPARATOR ', '), ''))) AS roles
             FROM users u
             LEFT JOIN user_staff_roles ur ON ur.user_id = u.id
             LEFT JOIN staff_roles r ON r.id = ur.staff_role_id
             WHERE u.role = 'admin' OR ur.user_id IS NOT NULL
             GROUP BY u.id, u.username, u.level, u.last_seen_at, u.role
             ORDER BY u.id",
        );
    }

    /** @return array<string, int> */
    public function statistics(): array
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS players,
                    COALESCE(SUM(money), 0) AS money,
                    COALESCE(SUM(crystals), 0) AS crystals,
                    SUM(bank_balance IS NOT NULL) AS bank_accounts,
                    COALESCE(SUM(bank_balance), 0) AS banked,
                    SUM(gender = 'Male') AS male,
                    SUM(gender = 'Female') AS female
             FROM users",
        ) ?? [];

        return array_map('intval', $row + [
            'items' => $this->db->fetchColumn('SELECT COALESCE(SUM(quantity), 0) FROM inventory'),
            'mail' => $this->db->fetchColumn('SELECT COUNT(*) FROM mail'),
            'events' => $this->db->fetchColumn('SELECT COUNT(*) FROM events'),
        ]);
    }

    public function profile(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT u.id, u.username, u.role, u.level, u.money, u.crystals, u.gender, u.created_at, u.last_seen_at,
                    u.last_login_at, u.hp, u.max_hp, u.duties, u.donator_until, u.avatar_url, u.account_type,
                    u.bank_balance IS NOT NULL AS has_bank, u.cyber_balance IS NOT NULL AS has_cyber,
                    u.jail_until, u.jail_reason, u.hospital_until, u.hospital_reason,
                    u.staff_notes, u.last_login_ip, u.signup_ip,
                    c.name AS city, h.name AS house, g.id AS gang_id, g.name AS gang,
                    f.jailed_until AS fed_until, f.reason AS fed_reason,
                    (SELECT COUNT(*) FROM referrals WHERE referrer_id = u.id) AS referrals,
                    (SELECT COUNT(*) FROM user_lists WHERE target_id = u.id AND list = 'friend') AS friends,
                    (SELECT COUNT(*) FROM user_lists WHERE target_id = u.id AND list = 'enemy') AS enemies
             FROM users u
             LEFT JOIN cities c ON c.id = u.city_id
             LEFT JOIN houses h ON h.id = u.house_id
             LEFT JOIN gangs g ON g.id = u.gang_id
             LEFT JOIN federal_jail f ON f.user_id = u.id
             WHERE u.id = ?",
            [$id],
        );
    }

    public function addReport(int $reporterId, int $reportedId, string $body, string $now): void
    {
        $this->db->insert('player_reports', [
            'reporter_id' => $reporterId,
            'reported_id' => $reportedId,
            'body' => $body,
            'created_at' => $now,
        ]);
    }
}
