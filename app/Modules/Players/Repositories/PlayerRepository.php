<?php

declare(strict_types=1);

namespace App\Modules\Players\Repositories;

use App\Core\Database;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use InvalidArgumentException;

final class PlayerRepository implements PlayerRepositoryInterface
{
    private const BALANCE_COLUMNS = ['bank_balance', 'cyber_balance'];

    private const CURRENCIES = ['money', 'crystals'];

    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?Player
    {
        // Stats default to 10 for accounts created before user_stats existed.
        $row = $this->db->fetch(
            'SELECT u.*,
                    COALESCE(s.strength, 10) AS strength, COALESCE(s.agility, 10) AS agility,
                    COALESCE(s.guard, 10) AS guard, COALESCE(s.labour, 10) AS labour,
                    COALESCE(s.iq, 10) AS iq
             FROM users u
             LEFT JOIN user_stats s ON s.user_id = u.id
             WHERE u.id = ?',
            [$id],
        );

        return $row === null ? null : Player::fromRow($row);
    }

    public function openAccount(int $id, string $column, int $cost): bool
    {
        $column = $this->balanceColumn($column);

        return $this->affects(
            "UPDATE users SET money = money - ?, {$column} = 0 WHERE id = ? AND money >= ? AND {$column} IS NULL",
            [$cost, $id, $cost],
        );
    }

    public function deposit(int $id, string $column, int $amount, int $credited): bool
    {
        $column = $this->balanceColumn($column);

        return $this->affects(
            "UPDATE users SET money = money - ?, {$column} = {$column} + ? WHERE id = ? AND money >= ? AND {$column} IS NOT NULL",
            [$amount, $credited, $id, $amount],
        );
    }

    public function withdraw(int $id, string $column, int $amount, int $paidOut): bool
    {
        $column = $this->balanceColumn($column);

        return $this->affects(
            "UPDATE users SET {$column} = {$column} - ?, money = money + ? WHERE id = ? AND {$column} >= ?",
            [$amount, $paidOut, $id, $amount],
        );
    }

    public function changeCurrency(int $id, string $currency, int $delta): bool
    {
        if (!in_array($currency, self::CURRENCIES, true)) {
            throw new InvalidArgumentException("Unknown currency [{$currency}].");
        }

        return $this->affects(
            "UPDATE users SET {$currency} = {$currency} + ? WHERE id = ? AND {$currency} + ? >= 0",
            [$delta, $id, $delta],
        );
    }

    public function levelUp(int $id): int
    {
        $gained = 0;

        // Each step only applies if level and EXP are still what we read.
        while ($gained < 100 && ($row = $this->db->fetch('SELECT level, exp FROM users WHERE id = ?', [$id])) !== null) {
            $needed = Player::expNeededFor((int) $row['level']);
            if ((int) $row['exp'] < $needed || !$this->affects(
                'UPDATE users SET level = level + 1, exp = exp - ?,
                    energy = energy + 2, max_energy = max_energy + 2, brave = brave + 2, max_brave = max_brave + 2,
                    hp = hp + 50, max_hp = max_hp + 50
                 WHERE id = ? AND level = ? AND exp >= ?',
                [$needed, $id, (int) $row['level'], $needed],
            )) {
                break;
            }
            $gained++;
        }

        return $gained;
    }

    public function spendBrave(int $id, int $cost): bool
    {
        return $this->affects('UPDATE users SET brave = brave - ? WHERE id = ? AND brave >= ?', [$cost, $id, $cost]);
    }

    public function rewardCrime(int $id, int $money, int $crystals, int $exp, int $crimeExp): void
    {
        $this->db->query(
            'UPDATE users SET money = money + ?, crystals = crystals + ?, exp = exp + ?, crime_exp = crime_exp + ? WHERE id = ?',
            [$money, $crystals, $exp, $crimeExp, $id],
        );
    }

    public function sendToJail(int $id, string $until, string $reason): void
    {
        $this->db->update('users', ['jail_until' => $until, 'jail_reason' => $reason], ['id' => $id]);
    }

    public function refillEnergy(int $id, int $crystalPrice): bool
    {
        return $this->affects(
            'UPDATE users SET energy = max_energy, crystals = crystals - ? WHERE id = ? AND crystals >= ? AND energy < max_energy',
            [$crystalPrice, $id, $crystalPrice],
        );
    }

    public function tradeCrystalsForMoney(int $id, int $crystals, int $money): bool
    {
        return $this->affects(
            'UPDATE users SET crystals = crystals - ?, money = money + ? WHERE id = ? AND crystals >= ?',
            [$crystals, $money, $id, $crystals],
        );
    }

    public function tradeCrystalsForIq(int $id, int $crystals, float $iq): bool
    {
        return $this->db->transaction(function () use ($id, $crystals, $iq): bool {
            if (!$this->affects('UPDATE users SET crystals = crystals - ? WHERE id = ? AND crystals >= ?', [$crystals, $id, $crystals])) {
                return false;
            }

            $this->db->query(
                'INSERT INTO user_stats (user_id, iq) VALUES (?, 10 + ?) ON DUPLICATE KEY UPDATE iq = iq + ?',
                [$id, $iq, $iq],
            );

            return true;
        });
    }

    public function touchLastSeen(int $id, string $now, string $staleBefore): void
    {
        $this->db->query(
            'UPDATE users SET last_seen_at = ? WHERE id = ? AND (last_seen_at IS NULL OR last_seen_at < ?)',
            [$now, $id, $staleBefore],
        );
    }

    public function federalJail(int $id, string $now): ?array
    {
        $row = $this->db->fetch('SELECT jailed_until, reason FROM federal_jail WHERE user_id = ? AND jailed_until > ?', [$id, $now]);

        return $row === null ? null : ['until' => (string) $row['jailed_until'], 'reason' => (string) $row['reason']];
    }

    public function transferBalance(int $fromId, int $toId, string $column, int $amount): bool
    {
        $column = $this->balanceColumn($column);

        return $this->db->transaction(function () use ($fromId, $toId, $column, $amount): bool {
            if (!$this->affects("UPDATE users SET {$column} = {$column} - ? WHERE id = ? AND {$column} >= ?", [$amount, $fromId, $amount])) {
                return false;
            }

            if (!$this->affects("UPDATE users SET {$column} = {$column} + ? WHERE id = ? AND {$column} IS NOT NULL", [$amount, $toId])) {
                // Receiver has no account: undo the deduction by aborting the transaction.
                throw new \App\Exceptions\ValidationException(['amount' => ['That player does not have an account to receive this.']]);
            }

            return true;
        });
    }

    public function regenerateAll(int $energyPercent, int $bravePercent, int $hpPercent, int $will): int
    {
        return $this->db->query(
            'UPDATE users SET
                energy = LEAST(max_energy, energy + GREATEST(1, FLOOR(max_energy * ? / 100))),
                brave = LEAST(max_brave, brave + GREATEST(1, FLOOR(max_brave * ? / 100))),
                hp = LEAST(max_hp, hp + GREATEST(1, FLOOR(max_hp * ? / 100))),
                will = LEAST(max_will, will + ?)
             WHERE energy < max_energy OR brave < max_brave OR hp < max_hp OR will < max_will',
            [$energyPercent, $bravePercent, $hpPercent, $will],
        )->rowCount();
    }

    public function applyInterest(string $column, float $percent): int
    {
        $column = $this->balanceColumn($column);

        return $this->db->query(
            "UPDATE users SET {$column} = {$column} + FLOOR({$column} * ? / 100) WHERE {$column} > 0",
            [$percent],
        )->rowCount();
    }

    private function balanceColumn(string $column): string
    {
        if (!in_array($column, self::BALANCE_COLUMNS, true)) {
            throw new InvalidArgumentException("Unknown balance column [{$column}].");
        }

        return $column;
    }

    private function affects(string $sql, array $params): bool
    {
        return $this->db->query($sql, $params)->rowCount() === 1;
    }
}
