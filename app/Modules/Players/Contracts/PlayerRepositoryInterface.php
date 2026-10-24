<?php

declare(strict_types=1);

namespace App\Modules\Players\Contracts;

use App\Modules\Players\Models\Player;

/**
 * Every write is a single conditional update: it only applies if the player
 * can still afford it, and returns false otherwise. Two simultaneous requests
 * therefore cannot spend the same money twice.
 */
interface PlayerRepositoryInterface
{
    public function find(int $id): ?Player;

    /** Pay $cost and set the balance column to 0, if no account exists yet. */
    public function openAccount(int $id, string $column, int $cost): bool;

    /** Take $amount from cash and add $credited (after fees) to the balance. */
    public function deposit(int $id, string $column, int $amount, int $credited): bool;

    /** Take $amount from the balance and add $paidOut (after fees) to cash. */
    public function withdraw(int $id, string $column, int $amount, int $paidOut): bool;

    /**
     * Add $delta (negative to take) to money or crystals. Taking fails, and
     * changes nothing, if the player has less than that.
     */
    public function changeCurrency(int $id, string $currency, int $delta): bool;

    /**
     * Legacy check_level(): while EXP covers the next level, level up (+2
     * energy and brave, +50 HP, current and maximum). Returns levels gained.
     */
    public function levelUp(int $id): int;

    public function spendBrave(int $id, int $cost): bool;

    public function rewardCrime(int $id, int $money, int $crystals, int $exp, int $crimeExp): void;

    public function sendToJail(int $id, string $until, string $reason): void;

    public function refillEnergy(int $id, int $crystalPrice): bool;

    public function tradeCrystalsForMoney(int $id, int $crystals, int $money): bool;

    public function tradeCrystalsForIq(int $id, int $crystals, float $iq): bool;

    /** Record activity for "last seen" / online lists; skipped if recorded after $staleBefore. */
    public function touchLastSeen(int $id, string $now, string $staleBefore): void;

    /** @return array{until: string, reason: string}|null Active federal jail sentence. */
    public function federalJail(int $id, string $now): ?array;

    /**
     * Move $amount between two players' bank or cyber balances. Fails (and
     * changes nothing) if the sender can't afford it or either has no account.
     */
    public function transferBalance(int $fromId, int $toId, string $column, int $amount): bool;

    /** Cron: restore energy, brave, HP and will for everyone. */
    public function regenerateAll(int $energyPercent, int $bravePercent, int $hpPercent, int $will): int;

    /** Cron: add daily interest to positive balances. */
    public function applyInterest(string $column, float $percent): int;
}
