<?php

declare(strict_types=1);

namespace App\Modules\Donations\Services;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Modules\Events\Services\EventService;

/**
 * Applies a donator pack from config('game.donator_packs'). Used by staff
 * (legacy staff_special.php givedp); a payment provider will call it too.
 */
final class DonatorPackService
{
    public function __construct(
        private readonly Database $db,
        private readonly EventService $events,
        private readonly ClockInterface $clock,
    ) {
    }

    /** @return array<string, array{name: string, money: int, crystals: int, iq: int, days: int}> */
    public function packs(): array
    {
        return config('game.donator_packs', []);
    }

    /** @return string the pack's name */
    public function grant(int $userId, string $packKey): string
    {
        $pack = $this->packs()[$packKey] ?? throw new NotFoundException('That donator pack does not exist.');
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->db->transaction(function () use ($userId, $pack, $now): void {
            $this->db->query(
                'UPDATE users SET money = money + ?, crystals = crystals + ?,
                    donator_until = GREATEST(COALESCE(donator_until, ?), ?) + INTERVAL ? DAY
                 WHERE id = ?',
                [$pack['money'], $pack['crystals'], $now, $now, $pack['days'], $userId],
            );
            if ($pack['iq'] > 0) {
                $this->db->query('INSERT INTO user_stats (user_id, iq) VALUES (?, 10 + ?) ON DUPLICATE KEY UPDATE iq = iq + ?', [$userId, $pack['iq'], $pack['iq']]);
            }
            $this->events->add($userId, "You were given one {$pack['name']} donator pack. Enjoy!");
        });

        return $pack['name'];
    }
}
