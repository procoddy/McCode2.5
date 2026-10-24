<?php

declare(strict_types=1);

namespace App\Modules\Gangs\Repositories;

use App\Core\Database;

/**
 * gangs, gang_applications, gang_events, gang_wars, gang_war_surrenders and
 * the gang side of users. Vault and membership changes are conditional updates.
 */
class GangRepository
{
    private const SELECT = 'SELECT g.*, p.username AS president_username, v.username AS vice_president_username,
            oc.name AS organised_crime_name,
            (SELECT COUNT(*) FROM users m WHERE m.gang_id = g.id) AS member_count
        FROM gangs g
        LEFT JOIN users p ON p.id = g.president_id
        LEFT JOIN users v ON v.id = g.vice_president_id
        LEFT JOIN organised_crimes oc ON oc.id = g.organised_crime_id';

    public function __construct(private readonly Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->fetchAll(self::SELECT . ' ORDER BY g.respect DESC, g.name');
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch(self::SELECT . ' WHERE g.id = ?', [$id]);
    }

    public function nameTaken(string $name, int $exceptId = 0): bool
    {
        return $this->db->fetchColumn('SELECT 1 FROM gangs WHERE name = ? AND id <> ?', [$name, $exceptId]) !== null;
    }

    public function members(int $gangId): array
    {
        return $this->db->fetchAll(
            'SELECT id, username, level, gang_joined_at, last_seen_at FROM users WHERE gang_id = ? ORDER BY gang_joined_at, level DESC',
            [$gangId],
        );
    }

    public function isMember(int $gangId, int $userId): bool
    {
        return $this->db->fetchColumn('SELECT 1 FROM users WHERE id = ? AND gang_id = ?', [$userId, $gangId]) !== null;
    }

    public function create(array $data): int
    {
        return $this->db->insert('gangs', $data);
    }

    public function update(int $id, array $data): void
    {
        $this->db->update('gangs', $data, ['id' => $id]);
    }

    /** False if the player is already in a gang. */
    public function join(int $userId, int $gangId, string $now): bool
    {
        return $this->db->query(
            'UPDATE users SET gang_id = ?, gang_joined_at = ? WHERE id = ? AND gang_id IS NULL',
            [$gangId, $now, $userId],
        )->rowCount() === 1;
    }

    /** False if the player wasn't in this gang. */
    public function leave(int $userId, int $gangId): bool
    {
        return $this->db->query(
            'UPDATE users SET gang_id = NULL, gang_joined_at = NULL WHERE id = ? AND gang_id = ?',
            [$userId, $gangId],
        )->rowCount() === 1;
    }

    public function events(int $gangId, int $limit = 10): array
    {
        return $this->db->fetchAll("SELECT body, created_at FROM gang_events WHERE gang_id = ? ORDER BY created_at DESC, id DESC LIMIT {$limit}", [$gangId]);
    }

    public function addEvent(int $gangId, string $body, string $now): void
    {
        $this->db->insert('gang_events', ['gang_id' => $gangId, 'body' => $body, 'created_at' => $now]);
    }

    public function applications(int $gangId): array
    {
        return $this->db->fetchAll(
            'SELECT a.id, a.body, a.created_at, u.id AS user_id, u.username, u.level, u.gang_id
             FROM gang_applications a JOIN users u ON u.id = a.user_id
             WHERE a.gang_id = ? ORDER BY a.created_at',
            [$gangId],
        );
    }

    public function application(int $id, int $gangId): ?array
    {
        return $this->db->fetch(
            'SELECT a.id, a.user_id, u.username, u.gang_id FROM gang_applications a JOIN users u ON u.id = a.user_id WHERE a.id = ? AND a.gang_id = ?',
            [$id, $gangId],
        );
    }

    /** False if the player already applied to this gang. */
    public function apply(int $gangId, int $userId, string $body, string $now): bool
    {
        return $this->db->query(
            'INSERT IGNORE INTO gang_applications (gang_id, user_id, body, created_at) VALUES (?, ?, ?, ?)',
            [$gangId, $userId, $body, $now],
        )->rowCount() === 1;
    }

    public function deleteApplication(int $id): void
    {
        $this->db->delete('gang_applications', ['id' => $id]);
    }

    public function deleteApplicationsOf(int $userId): void
    {
        $this->db->delete('gang_applications', ['user_id' => $userId]);
    }

    public function deposit(int $gangId, int $money, int $crystals): void
    {
        $this->db->query('UPDATE gangs SET money = money + ?, crystals = crystals + ? WHERE id = ?', [$money, $crystals, $gangId]);
    }

    /** Take from the vault; false (nothing taken) if it holds less. */
    public function withdraw(int $gangId, int $money, int $crystals): bool
    {
        return $this->db->query(
            'UPDATE gangs SET money = money - ?, crystals = crystals - ? WHERE id = ? AND money >= ? AND crystals >= ?',
            [$money, $crystals, $gangId, $money, $crystals],
        )->rowCount() === 1;
    }

    /** Pay for extra member slots from the vault. */
    public function buyCapacity(int $gangId, int $slots, int $cost): bool
    {
        return $this->db->query(
            'UPDATE gangs SET capacity = capacity + ?, money = money - ? WHERE id = ? AND money >= ?',
            [$slots, $cost, $gangId, $cost],
        )->rowCount() === 1;
    }

    /** Give every member $amount from the vault, if it covers them all. Returns members paid, or 0. */
    public function payMembers(int $gangId, int $amount): int
    {
        return $this->db->transaction(function () use ($gangId, $amount): int {
            $count = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM users WHERE gang_id = ?', [$gangId]);

            if ($count === 0 || !$this->withdraw($gangId, $amount * $count, 0)) {
                return 0;
            }

            $this->db->query('UPDATE users SET money = money + ? WHERE gang_id = ?', [$amount, $gangId]);

            return $count;
        });
    }

    public function wars(int $gangId): array
    {
        return $this->db->fetchAll(
            'SELECT w.id, w.created_at, w.declarer_gang_id, w.declared_gang_id,
                    IF(w.declarer_gang_id = ?, w.declared_gang_id, w.declarer_gang_id) AS enemy_id,
                    e.name AS enemy_name, e.respect AS enemy_respect
             FROM gang_wars w
             JOIN gangs e ON e.id = IF(w.declarer_gang_id = ?, w.declared_gang_id, w.declarer_gang_id)
             WHERE w.declarer_gang_id = ? OR w.declared_gang_id = ?
             ORDER BY w.created_at DESC',
            [$gangId, $gangId, $gangId, $gangId],
        );
    }

    public function war(int $id, int $gangId): ?array
    {
        return $this->db->fetch(
            'SELECT id, declarer_gang_id, declared_gang_id, IF(declarer_gang_id = ?, declared_gang_id, declarer_gang_id) AS enemy_id
             FROM gang_wars WHERE id = ? AND (declarer_gang_id = ? OR declared_gang_id = ?)',
            [$gangId, $id, $gangId, $gangId],
        );
    }

    /** False if the two gangs are already at war. */
    public function declareWar(int $gangId, int $enemyId, string $now): bool
    {
        if ($this->db->fetchColumn(
            'SELECT 1 FROM gang_wars WHERE (declarer_gang_id = ? AND declared_gang_id = ?) OR (declarer_gang_id = ? AND declared_gang_id = ?)',
            [$gangId, $enemyId, $enemyId, $gangId],
        ) !== null) {
            return false;
        }

        $this->db->insert('gang_wars', ['declarer_gang_id' => $gangId, 'declared_gang_id' => $enemyId, 'created_at' => $now]);

        return true;
    }

    public function endWar(int $warId): void
    {
        $this->db->delete('gang_wars', ['id' => $warId]);
    }

    public function offerSurrender(int $warId, int $fromGang, int $toGang, string $message, string $now): void
    {
        $this->db->insert('gang_war_surrenders', [
            'war_id' => $warId, 'from_gang_id' => $fromGang, 'to_gang_id' => $toGang, 'message' => $message, 'created_at' => $now,
        ]);
    }

    public function surrendersTo(int $gangId): array
    {
        return $this->db->fetchAll(
            'SELECT s.id, s.war_id, s.message, s.created_at, g.id AS from_gang_id, g.name AS from_gang_name
             FROM gang_war_surrenders s JOIN gangs g ON g.id = s.from_gang_id
             WHERE s.to_gang_id = ? ORDER BY s.created_at',
            [$gangId],
        );
    }

    public function surrender(int $id, int $toGang): ?array
    {
        return $this->db->fetch('SELECT * FROM gang_war_surrenders WHERE id = ? AND to_gang_id = ?', [$id, $toGang]);
    }

    /** Organised crimes the gang has enough members for. */
    public function availableCrimes(int $members): array
    {
        return $this->db->fetchAll('SELECT * FROM organised_crimes WHERE members_required <= ? ORDER BY members_required, name', [$members]);
    }

    public function organisedCrime(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM organised_crimes WHERE id = ?', [$id]);
    }

    /** Plan a crime; false if one is already planned. */
    public function planCrime(int $gangId, int $crimeId, string $readyAt): bool
    {
        return $this->db->query(
            'UPDATE gangs SET organised_crime_id = ?, crime_ready_at = ? WHERE id = ? AND organised_crime_id IS NULL',
            [$crimeId, $readyAt, $gangId],
        )->rowCount() === 1;
    }

    /** Gangs whose planned crime is ready (cron). */
    public function readyCrimes(string $now): array
    {
        return $this->db->fetchAll(
            'SELECT g.id AS gang_id, g.name AS gang_name, oc.*
             FROM gangs g JOIN organised_crimes oc ON oc.id = g.organised_crime_id
             WHERE g.crime_ready_at <= ?',
            [$now],
        );
    }

    /** Record the result and clear the plan, once. Returns the log id, or null if another run got there first. */
    public function completeCrime(int $gangId, array $crime, bool $success, int $money, string $log, string $now): ?int
    {
        return $this->db->transaction(function () use ($gangId, $crime, $success, $money, $log, $now): ?int {
            if ($this->db->query(
                'UPDATE gangs SET organised_crime_id = NULL, crime_ready_at = NULL, money = money + ? WHERE id = ? AND organised_crime_id = ?',
                [$money, $gangId, $crime['id']],
            )->rowCount() !== 1) {
                return null;
            }

            return $this->db->insert('organised_crime_logs', [
                'organised_crime_id' => $crime['id'],
                'gang_id' => $gangId,
                'crime_name' => $crime['name'],
                'result' => $success ? 'success' : 'failure',
                'money' => $money,
                'log' => $log,
                'created_at' => $now,
            ]);
        });
    }

    public function crimeLogs(int $gangId, int $limit = 20): array
    {
        return $this->db->fetchAll("SELECT * FROM organised_crime_logs WHERE gang_id = ? ORDER BY created_at DESC, id DESC LIMIT {$limit}", [$gangId]);
    }

    /** Recent fights involving current members. */
    public function attackLogs(int $gangId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT l.*, a.username AS attacker, d.username AS defender, a.gang_id AS attacker_gang
             FROM attack_logs l
             JOIN users a ON a.id = l.attacker_id
             JOIN users d ON d.id = l.defender_id
             WHERE a.gang_id = ? OR d.gang_id = ?
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT {$limit}",
            [$gangId, $gangId],
        );
    }

    /** Legacy gang forum: one per gang, created on first visit. */
    public function forumId(int $gangId, string $name): int
    {
        $id = $this->db->fetchColumn("SELECT id FROM forums WHERE access = 'gang' AND gang_id = ?", [$gangId]);

        if ($id !== null) {
            $this->db->update('forums', ['name' => $name], ['id' => (int) $id]);

            return (int) $id;
        }

        return $this->db->insert('forums', ['name' => $name, 'access' => 'gang', 'gang_id' => $gangId]);
    }
}
