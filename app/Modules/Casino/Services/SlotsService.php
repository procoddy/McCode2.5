<?php

declare(strict_types=1);

namespace App\Modules\Casino\Services;

use App\Contracts\RandomInterface;
use App\Exceptions\ValidationException;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;

/**
 * Legacy slotsmachine.php: three digits; three of a kind pays 26x the bet
 * back, two of a kind 3x. The legacy "tresde" refresh guard is replaced by
 * POST + CSRF, and the bet is taken before the spin so it can't be skipped.
 */
final class SlotsService
{
    public function __construct(
        private readonly PlayerRepositoryInterface $players,
        private readonly RandomInterface $random,
    ) {
    }

    public function maxBet(Player $player): int
    {
        return $player->level * (int) config('game.slots.max_bet_per_level', 250);
    }

    /** @return array{reels: list<int>, bet: int, won: int, matched: int} */
    public function spin(Player $player, int $bet): array
    {
        if ($bet > $this->maxBet($player)) {
            throw ValidationException::withMessages(['bet' => 'You have gone over the max bet.']);
        }

        if (!$this->players->changeCurrency($player->id, 'money', -$bet)) {
            throw ValidationException::withMessages(['bet' => 'You are trying to bet more than you have.']);
        }

        $reels = [$this->random->int(0, 9), $this->random->int(0, 9), $this->random->int(0, 9)];
        $matched = match (true) {
            $reels[0] === $reels[1] && $reels[1] === $reels[2] => 3,
            $reels[0] === $reels[1] || $reels[1] === $reels[2] || $reels[0] === $reels[2] => 2,
            default => 0,
        };
        $won = match ($matched) { 3 => $bet * 26, 2 => $bet * 3, default => 0 };

        if ($won > 0) {
            $this->players->changeCurrency($player->id, 'money', $won);
        }

        return ['reels' => $reels, 'bet' => $bet, 'won' => $won, 'matched' => $matched];
    }
}
