<?php

declare(strict_types=1);

namespace App\Modules\CrystalTemple\Services;

use App\Exceptions\ValidationException;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use App\Support\Money;

/**
 * Legacy crystaltemple.php: spend crystals on an energy refill, IQ or money.
 */
final class CrystalTempleService
{
    public function __construct(private readonly PlayerRepositoryInterface $players)
    {
    }

    public function refillPrice(): int
    {
        return (int) config('game.crystal_temple.refill_price');
    }

    public function iqPerCrystal(): float
    {
        return (float) config('game.crystal_temple.iq_per_crystal');
    }

    public function moneyPerCrystal(): int
    {
        return (int) config('game.crystal_temple.money_per_crystal');
    }

    public function refill(Player $player): string
    {
        $price = $this->refillPrice();

        if ($player->energy >= $player->maxEnergy) {
            throw ValidationException::withMessages(['refill' => 'You already have full energy.']);
        }

        if ($player->crystals < $price || !$this->players->refillEnergy($player->id, $price)) {
            throw ValidationException::withMessages(['refill' => "You don't have enough {$this->name()}."]);
        }

        return "You paid {$price} {$this->name()} to refill your energy.";
    }

    public function tradeForIq(Player $player, int $crystals): string
    {
        $this->requireCrystals($player, $crystals, 'iq_crystals');
        $iq = $crystals * $this->iqPerCrystal();

        if (!$this->players->tradeCrystalsForIq($player->id, $crystals, $iq)) {
            $this->notEnough('iq_crystals');
        }

        return "You traded {$crystals} {$this->name()} for {$iq} IQ.";
    }

    public function tradeForMoney(Player $player, int $crystals): string
    {
        $this->requireCrystals($player, $crystals, 'money_crystals');
        $money = $crystals * $this->moneyPerCrystal();

        if (!$this->players->tradeCrystalsForMoney($player->id, $crystals, $money)) {
            $this->notEnough('money_crystals');
        }

        return "You traded {$crystals} {$this->name()} for " . Money::of($money)->format() . '.';
    }

    private function requireCrystals(Player $player, int $crystals, string $field): void
    {
        if ($crystals > $player->crystals) {
            $this->notEnough($field);
        }
    }

    private function notEnough(string $field): never
    {
        throw ValidationException::withMessages([$field => "You don't have that many {$this->name()}."]);
    }

    private function name(): string
    {
        return strtolower((string) config('game.crystals_name'));
    }
}
