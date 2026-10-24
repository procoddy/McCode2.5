<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Players\Models\Player;
use InvalidArgumentException;

/**
 * One effect of using an item (legacy items.effect1..3, which were serialized
 * PHP arrays; now one entry of the items.effects JSON list).
 */
final class ItemEffect
{
    /** stat key => label, in the order the editor lists them. */
    public const STATS = [
        'energy' => 'Energy',
        'will' => 'Will',
        'brave' => 'Brave',
        'hp' => 'Health',
        'strength' => 'Strength',
        'agility' => 'Agility',
        'guard' => 'Guard',
        'labour' => 'Labour',
        'iq' => 'IQ',
        'hospital' => 'Hospital time (minutes)',
        'jail' => 'Jail time (minutes)',
        'money' => 'Money',
        'crystals' => 'Crystals',
        'course_days' => 'Education days left',
        'bank_balance' => 'Bank money',
        'cyber_balance' => 'Cyber money',
        'crime_exp' => 'Crime XP',
    ];

    /** Bars whose percentages are of the maximum, and which can't go over it. */
    public const BARS = ['energy' => 'maxEnergy', 'will' => 'maxWill', 'brave' => 'maxBrave', 'hp' => 'maxHp'];

    public const BATTLE_STATS = ['strength', 'agility', 'guard', 'labour', 'iq'];

    public const TIMERS = ['hospital', 'jail', 'course_days'];

    /** Legacy stat names, for importing old serialized effects. */
    public const LEGACY_NAMES = ['IQ' => 'iq', 'cdays' => 'course_days', 'bankmoney' => 'bank_balance', 'cybermoney' => 'cyber_balance', 'crimexp' => 'crime_exp'];

    public function __construct(
        public readonly string $stat,
        public readonly bool $increase,
        public readonly bool $percent,
        public readonly int $amount,
    ) {
        if (!isset(self::STATS[$stat])) {
            throw new InvalidArgumentException("Unknown item effect stat [{$stat}].");
        }
    }

    public static function fromArray(array $data): self
    {
        $stat = (string) ($data['stat'] ?? '');

        return new self(
            self::LEGACY_NAMES[$stat] ?? $stat,
            ($data['direction'] ?? $data['dir'] ?? 'increase') !== 'decrease' && ($data['dir'] ?? null) !== 'neg',
            ($data['type'] ?? $data['inc_type'] ?? 'figure') === 'percent',
            abs((int) ($data['amount'] ?? $data['inc_amount'] ?? 0)),
        );
    }

    public function toArray(): array
    {
        return [
            'stat' => $this->stat,
            'direction' => $this->increase ? 'increase' : 'decrease',
            'type' => $this->percent ? 'percent' : 'figure',
            'amount' => $this->amount,
        ];
    }

    /**
     * Signed change for this player: units of the stat, minutes for hospital
     * and jail, days for course_days. Percentages of a bar are of its maximum;
     * of anything else, of the current value (remaining time for timers).
     */
    public function delta(Player $player, string $now): int
    {
        if (!$this->percent) {
            $change = $this->amount;
        } else {
            $base = match (true) {
                isset(self::BARS[$this->stat]) => $player->{self::BARS[$this->stat]},
                $this->stat === 'hospital' => self::minutesLeft($player->hospitalUntil, $now),
                $this->stat === 'jail' => self::minutesLeft($player->jailUntil, $now),
                $this->stat === 'course_days' => intdiv(self::minutesLeft($player->courseEndsAt, $now), 1440),
                default => $this->current($player),
            };
            $change = (int) floor($base * $this->amount / 100);
        }

        return $this->increase ? $change : -$change;
    }

    public function describe(): string
    {
        $amount = $this->percent ? "{$this->amount}%" : number_format($this->amount);

        return ($this->increase ? 'Increases ' : 'Decreases ') . strtolower(self::STATS[$this->stat]) . " by {$amount}";
    }

    private function current(Player $player): float|int
    {
        return match ($this->stat) {
            'strength' => $player->strength,
            'agility' => $player->agility,
            'guard' => $player->guard,
            'labour' => $player->labour,
            'iq' => $player->iq,
            'money' => $player->money->amount(),
            'crystals' => $player->crystals,
            'bank_balance' => $player->bankBalance ?? 0,
            'cyber_balance' => $player->cyberBalance ?? 0,
            'crime_exp' => $player->crimeExp,
        };
    }

    private static function minutesLeft(?string $until, string $now): int
    {
        return $until === null || $until <= $now ? 0 : intdiv(strtotime($until) - strtotime($now), 60);
    }
}
