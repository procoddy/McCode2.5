<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use App\Support\Money;

/**
 * Same conditional behaviour as PlayerRepository, kept in memory.
 * Unknown ids get a default player so tests that don't care about stats still work.
 */
final class InMemoryPlayerRepository implements PlayerRepositoryInterface
{
    /** @var array<int, Player> */
    public array $players = [];

    public function add(int $id, array $attributes = []): Player
    {
        return $this->players[$id] = new Player(...['id' => $id, 'username' => "player{$id}", ...$attributes]);
    }

    public function find(int $id): ?Player
    {
        return $this->players[$id] ?? $this->add($id);
    }

    public function openAccount(int $id, string $column, int $cost): bool
    {
        $p = $this->find($id);

        return $p->balance($column) === null && $p->money->amount() >= $cost
            && $this->change($id, ['money' => Money::of($p->money->amount() - $cost), $this->property($column) => 0]);
    }

    public function deposit(int $id, string $column, int $amount, int $credited): bool
    {
        $p = $this->find($id);

        return $p->balance($column) !== null && $p->money->amount() >= $amount
            && $this->change($id, [
                'money' => Money::of($p->money->amount() - $amount),
                $this->property($column) => $p->balance($column) + $credited,
            ]);
    }

    public function withdraw(int $id, string $column, int $amount, int $paidOut): bool
    {
        $p = $this->find($id);

        return ($p->balance($column) ?? -1) >= $amount
            && $this->change($id, [
                'money' => Money::of($p->money->amount() + $paidOut),
                $this->property($column) => $p->balance($column) - $amount,
            ]);
    }

    public function changeCurrency(int $id, string $currency, int $delta): bool
    {
        $p = $this->find($id);
        $current = $currency === 'money' ? $p->money->amount() : $p->crystals;

        return $current + $delta >= 0
            && $this->change($id, [$currency => $currency === 'money' ? Money::of($current + $delta) : $current + $delta]);
    }

    public function levelUp(int $id): int
    {
        $gained = 0;
        while (($p = $this->find($id))->exp >= $p->expNeeded()) {
            $this->change($id, [
                'level' => $p->level + 1, 'exp' => $p->exp - $p->expNeeded(),
                'energy' => $p->energy + 2, 'maxEnergy' => $p->maxEnergy + 2, 'brave' => $p->brave + 2, 'maxBrave' => $p->maxBrave + 2,
                'hp' => $p->hp + 50, 'maxHp' => $p->maxHp + 50,
            ]);
            $gained++;
        }

        return $gained;
    }

    public function spendBrave(int $id, int $cost): bool
    {
        $p = $this->find($id);

        return $p->brave >= $cost && $this->change($id, ['brave' => $p->brave - $cost]);
    }

    public function rewardCrime(int $id, int $money, int $crystals, int $exp, int $crimeExp): void
    {
        $p = $this->find($id);
        $this->change($id, [
            'money' => Money::of($p->money->amount() + $money),
            'crystals' => $p->crystals + $crystals,
            'exp' => $p->exp + $exp,
            'crimeExp' => $p->crimeExp + $crimeExp,
        ]);
    }

    public function sendToJail(int $id, string $until, string $reason): void
    {
        $this->change($id, ['jailUntil' => $until, 'jailReason' => $reason]);
    }

    public function refillEnergy(int $id, int $crystalPrice): bool
    {
        $p = $this->find($id);

        return $p->crystals >= $crystalPrice && $p->energy < $p->maxEnergy
            && $this->change($id, ['energy' => $p->maxEnergy, 'crystals' => $p->crystals - $crystalPrice]);
    }

    public function tradeCrystalsForMoney(int $id, int $crystals, int $money): bool
    {
        $p = $this->find($id);

        return $p->crystals >= $crystals
            && $this->change($id, ['crystals' => $p->crystals - $crystals, 'money' => Money::of($p->money->amount() + $money)]);
    }

    public function tradeCrystalsForIq(int $id, int $crystals, float $iq): bool
    {
        $p = $this->find($id);

        return $p->crystals >= $crystals && $this->change($id, ['crystals' => $p->crystals - $crystals, 'iq' => $p->iq + $iq]);
    }

    /** @var array<int, array{until: string, reason: string}> */
    public array $federalJail = [];

    public function touchLastSeen(int $id, string $now, string $staleBefore): void
    {
    }

    public function federalJail(int $id, string $now): ?array
    {
        $sentence = $this->federalJail[$id] ?? null;

        return $sentence !== null && $sentence['until'] > $now ? $sentence : null;
    }

    public function transferBalance(int $fromId, int $toId, string $column, int $amount): bool
    {
        $from = $this->find($fromId);
        $to = $this->find($toId);

        if (($from->balance($column) ?? -1) < $amount) {
            return false;
        }
        if ($to->balance($column) === null) {
            throw new \App\Exceptions\ValidationException(['amount' => ['That player does not have an account to receive this.']]);
        }

        $this->change($fromId, [$this->property($column) => $from->balance($column) - $amount]);
        $this->change($toId, [$this->property($column) => $to->balance($column) + $amount]);

        return true;
    }

    public function regenerateAll(int $energyPercent, int $bravePercent, int $hpPercent, int $will): int
    {
        return 0;
    }

    public function applyInterest(string $column, float $percent): int
    {
        return 0;
    }

    private function change(int $id, array $changes): bool
    {
        $this->players[$id] = new Player(...[...get_object_vars($this->find($id)), ...$changes]);

        return true;
    }

    private function property(string $column): string
    {
        return $column === 'bank_balance' ? 'bankBalance' : 'cyberBalance';
    }
}
