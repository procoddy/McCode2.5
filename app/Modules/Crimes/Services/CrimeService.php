<?php

declare(strict_types=1);

namespace App\Modules\Crimes\Services;

use App\Contracts\ClockInterface;
use App\Contracts\RandomInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Crimes\Contracts\CrimeRepositoryInterface;
use App\Modules\Crimes\Models\Crime;
use App\Modules\Crimes\Models\CrimeOutcome;
use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use App\Support\Formula;
use App\Support\Money;

/**
 * Legacy criminal.php / docrime.php.
 */
final class CrimeService
{
    public function __construct(
        private readonly CrimeRepositoryInterface $crimes,
        private readonly PlayerRepositoryInterface $players,
        private readonly RandomInterface $random,
        private readonly ClockInterface $clock,
        private readonly InventoryRepository $inventory,
    ) {
    }

    /** Why the player cannot do crimes right now, or null if they can. */
    public function blockedReason(Player $player): ?string
    {
        $now = $this->now();

        return match (true) {
            $player->isJailed($now) => 'You are in jail until ' . date('g:i a', strtotime($player->jailUntil)) . '.',
            $player->isHospitalized($now) => 'You are in hospital until ' . date('g:i a', strtotime($player->hospitalUntil)) . '.',
            default => null,
        };
    }

    /** Success chance in percent (0–100) for this player. */
    public function successChance(Player $player, Crime $crime): float
    {
        $chance = Formula::evaluate($crime->successFormula, [
            'LEVEL' => $player->level,
            'CRIMEXP' => $player->crimeExp,
            'EXP' => $player->exp,
            'WILL' => $player->will,
            'IQ' => $player->iq,
        ]);

        return max(0.0, min(100.0, $chance));
    }

    public function commit(Player $player, int $crimeId): CrimeOutcome
    {
        $crime = $this->crimes->find($crimeId) ?? throw new NotFoundException('That crime does not exist.');

        if (($reason = $this->blockedReason($player)) !== null) {
            throw ValidationException::withMessages(['crime' => $reason]);
        }

        if ($player->brave < $crime->braveCost || !$this->players->spendBrave($player->id, $crime->braveCost)) {
            throw ValidationException::withMessages(['crime' => 'You do not have enough brave to perform this crime.']);
        }

        if ($this->random->int(1, 100) <= $this->successChance($player, $crime)) {
            // Legacy: EXP gain is an eighth of the money reward.
            $this->players->rewardCrime(
                $player->id,
                $crime->successMoney,
                $crime->successCrystals,
                intdiv($crime->successMoney, 8),
                $crime->crimeExp,
            );
            $text = str_replace('{money}', Money::of($crime->successMoney)->format(), $crime->successText);

            if ($crime->successItemId !== null && ($item = $this->inventory->findItem($crime->successItemId)) !== null) {
                $this->inventory->add($player->id, $item->id, 1);
                $text .= " You also found a {$item->name}.";
            }

            return new CrimeOutcome($crime, CrimeOutcome::SUCCESS, $text);
        }

        if ($this->random->int(1, 2) === 1) {
            return new CrimeOutcome($crime, CrimeOutcome::FAILURE, $crime->failureText);
        }

        $until = $this->clock->now()->modify("+{$crime->jailMinutes} minutes")->format('Y-m-d H:i:s');
        $this->players->sendToJail($player->id, $until, $crime->jailReason);

        return new CrimeOutcome($crime, CrimeOutcome::JAILED, $crime->jailText, $until);
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
