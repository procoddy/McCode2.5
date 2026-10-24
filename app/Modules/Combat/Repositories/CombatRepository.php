<?php

declare(strict_types=1);

namespace App\Modules\Combat\Repositories;

use App\Core\Database;
use App\Modules\Combat\Models\Fighter;

class CombatRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function fighter(int $id): ?Fighter
    {
        $row = $this->db->fetch(
            'SELECT u.id, u.username, u.gender, u.level, u.hp, u.max_hp, u.energy, u.max_energy, u.money,
                    u.gang_id, u.city_id, u.hospital_until, u.jail_until, u.attacking_user_id, u.account_type,
                    u.equipped_primary_id, u.equipped_secondary_id,
                    COALESCE(s.strength, 10) AS strength, COALESCE(s.agility, 10) AS agility, COALESCE(s.guard, 10) AS guard,
                    COALESCE(a.armor, 0) AS armor
             FROM users u
             LEFT JOIN user_stats s ON s.user_id = u.id
             LEFT JOIN items a ON a.id = u.equipped_armor_id
             WHERE u.id = ?',
            [$id],
        );

        if ($row === null) {
            return null;
        }

        $weapons = [];
        foreach ($this->db->fetchAll(
            'SELECT id, name, weapon FROM items WHERE id IN (?, ?) AND weapon > 0',
            [$row['equipped_primary_id'] ?? 0, $row['equipped_secondary_id'] ?? 0],
        ) as $weapon) {
            $weapons[(int) $weapon['id']] = ['name' => (string) $weapon['name'], 'power' => (int) $weapon['weapon']];
        }

        return new Fighter(
            id: (int) $row['id'],
            username: (string) $row['username'],
            gender: (string) $row['gender'],
            level: (int) $row['level'],
            hp: (int) $row['hp'],
            maxHp: (int) $row['max_hp'],
            energy: (int) $row['energy'],
            maxEnergy: (int) $row['max_energy'],
            money: (int) $row['money'],
            strength: (float) $row['strength'],
            agility: (float) $row['agility'],
            guard: (float) $row['guard'],
            armor: (int) $row['armor'],
            weapons: $weapons,
            gangId: $row['gang_id'] === null ? null : (int) $row['gang_id'],
            cityId: $row['city_id'] === null ? null : (int) $row['city_id'],
            hospitalUntil: $row['hospital_until'],
            jailUntil: $row['jail_until'],
            attackingUserId: $row['attacking_user_id'] === null ? null : (int) $row['attacking_user_id'],
            isNpc: $row['account_type'] === 'NPC',
        );
    }

    /** Pay half your maximum energy and lock onto the target. False if not enough energy or already fighting. */
    public function startFight(int $attackerId, int $targetId, int $cost): bool
    {
        return $this->db->query(
            'UPDATE users SET energy = energy - ?, attacking_user_id = ? WHERE id = ? AND energy >= ? AND attacking_user_id IS NULL',
            [$cost, $targetId, $attackerId, $cost],
        )->rowCount() === 1;
    }

    public function endFight(int $attackerId): void
    {
        $this->db->query('UPDATE users SET attacking_user_id = NULL WHERE id = ?', [$attackerId]);
    }

    /** Remove HP; landing on exactly 1 (legacy "unconscious") counts as 0. Returns HP left. */
    public function damage(int $id, int $damage): int
    {
        $this->db->query('UPDATE users SET hp = IF(hp - ? <= 1, 0, hp - ?) WHERE id = ?', [$damage, $damage, $id]);

        return (int) $this->db->fetchColumn('SELECT hp FROM users WHERE id = ?', [$id]);
    }

    /**
     * Lock a knocked-out defender (0 HP) for the winner's choice. Null if they
     * are no longer at 0 HP, so the same win can't be cashed in twice.
     *
     * @return array{money: int}|null
     */
    public function lockBeaten(int $id): ?array
    {
        $row = $this->db->fetch('SELECT money FROM users WHERE id = ? AND hp = 0 FOR UPDATE', [$id]);

        return $row === null ? null : ['money' => (int) $row['money']];
    }

    /** Defender goes to hospital with 1 HP, minus any money stolen. */
    public function hospitalize(int $id, string $until, string $reason, int $moneyStolen): void
    {
        $this->db->query(
            'UPDATE users SET hp = 1, hospital_until = ?, hospital_reason = ?, money = money - ? WHERE id = ?',
            [$until, $reason, $moneyStolen, $id],
        );
    }

    public function reward(int $id, int $money, int $exp): void
    {
        $this->db->query('UPDATE users SET money = money + ?, exp = exp + ? WHERE id = ?', [$money, $exp, $id]);
    }

    public function loseExp(int $id, int $exp): void
    {
        $this->db->query('UPDATE users SET exp = GREATEST(0, exp - ?) WHERE id = ?', [$exp, $id]);
    }

    public function log(array $data): void
    {
        $this->db->insert('attack_logs', $data);
    }

    /** Challenge-bot bounty, paid only the first time this player beats the bot. */
    public function claimBotReward(int $userId, int $botId, string $now): int
    {
        $reward = $this->db->fetchColumn('SELECT reward_money FROM challenge_bots WHERE user_id = ?', [$botId]);

        if ($reward === null) {
            return 0;
        }

        $claimed = $this->db->query(
            'INSERT IGNORE INTO challenge_bot_wins (user_id, bot_user_id, won_at) VALUES (?, ?, ?)',
            [$userId, $botId, $now],
        )->rowCount() === 1;

        return $claimed ? (int) $reward : 0;
    }

    /** Battle tent rows for one player. */
    public function challengeBots(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT u.id, u.username, u.level, u.hp, u.max_hp, u.city_id, u.hospital_until, u.jail_until,
                    c.name AS city, b.reward_money,
                    (SELECT COUNT(*) FROM challenge_bot_wins w WHERE w.bot_user_id = b.user_id) AS times_beaten,
                    EXISTS (SELECT 1 FROM challenge_bot_wins w WHERE w.bot_user_id = b.user_id AND w.user_id = ?) AS beaten_by_me
             FROM challenge_bots b
             JOIN users u ON u.id = b.user_id
             LEFT JOIN cities c ON c.id = u.city_id
             ORDER BY u.level, u.username',
            [$userId],
        );
    }
}
