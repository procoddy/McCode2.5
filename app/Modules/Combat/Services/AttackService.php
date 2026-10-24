<?php

declare(strict_types=1);

namespace App\Modules\Combat\Services;

use App\Contracts\ClockInterface;
use App\Contracts\RandomInterface;
use App\Core\Database;
use App\Core\Session;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Combat\Models\Fighter;
use App\Modules\Combat\Repositories\CombatRepository;
use App\Modules\Events\Services\EventService;
use App\Modules\Gangs\Repositories\GangWarRepository;
use App\Modules\Players\Models\Player;
use App\Support\Money;

/**
 * Legacy attack.php, attackwon.php (mug), attackbeat.php (hospitalize),
 * attacktake.php (leave) and attacklost.php.
 *
 * The fight's running log lives in the session like the legacy one did; who
 * is fighting whom is users.attacking_user_id. Losing is applied as soon as
 * your HP hits 0 (legacy waited for a "Continue" click that could be skipped).
 */
final class AttackService
{
    public const CHOICES = ['mug', 'hospitalize', 'leave'];

    private const SESSION_KEY = 'attack';

    /** Respect moved between gangs at war, by outcome. */
    private const RESPECT = ['mug' => 2, 'hospitalize' => 3, 'leave' => 1, 'lost' => 1];

    public function __construct(
        private readonly CombatRepository $combat,
        private readonly CombatCalculator $calculator,
        private readonly GangWarRepository $wars,
        private readonly EventService $events,
        private readonly Session $session,
        private readonly Database $db,
        private readonly RandomInterface $random,
        private readonly ClockInterface $clock,
    ) {
    }

    /** @return array{0: Fighter, 1: Fighter} */
    public function fighters(Player $player, int $targetId): array
    {
        if ($targetId === $player->id) {
            throw ValidationException::withMessages(['attack' => "You can't attack yourself."]);
        }

        return [
            $this->combat->fighter($player->id) ?? throw new NotFoundException(),
            $this->combat->fighter($targetId) ?? throw new NotFoundException("That player doesn't exist."),
        ];
    }

    /** Why $me can't hit $target right now, or null. */
    public function blockedReason(Fighter $me, Fighter $target): ?string
    {
        $now = $this->now();
        $fighting = $me->attackingUserId === $target->id;

        return match (true) {
            $me->attackingUserId !== null && !$fighting => "You're still in another fight.",
            $me->hp <= 1 => "You're unconscious, so you can't attack.",
            $me->isHospitalized($now) => "While in hospital you can't attack.",
            $me->isJailed($now) => "While in jail you can't attack.",
            $target->hp === 1 => 'This player is unconscious.',
            $target->isHospitalized($now) => 'This player is in hospital.',
            $target->isJailed($now) => 'This player is in jail.',
            $fighting => null,
            $target->hp < 5 => 'You can only attack those who have health.',
            $me->gangId !== null && $me->gangId === $target->gangId => "You are in the same gang as {$target->username}!",
            $me->energy < $me->attackCost() || $me->energy * 2 < $me->maxEnergy => 'You can only attack someone when you have 50% energy.',
            $me->cityId !== $target->cityId => 'You can only attack someone in the same city!',
            default => null,
        };
    }

    /** The current fight's log lines, if the session's fight is against $targetId. */
    public function log(int $targetId): array
    {
        $state = $this->session->get(self::SESSION_KEY);

        return ($state['target'] ?? null) === $targetId ? $state['log'] : [];
    }

    /** True if $targetId is knocked out and waiting for the winner's choice. */
    public function awaitingChoice(int $targetId): bool
    {
        return ($this->session->get(self::SESSION_KEY)['won'] ?? null) === $targetId;
    }

    /**
     * One round: your hit, then theirs if they're still standing.
     *
     * @return array{result: 'continue'|'won'|'lost', lines: list<array{side: string, text: string}>, message: ?string}
     */
    public function strike(Player $player, int $targetId, int $weaponId): array
    {
        [$me, $target] = $this->fighters($player, $targetId);

        if ($this->awaitingChoice($target->id)) {
            throw ValidationException::withMessages(['attack' => "{$target->username} is down. Choose what to do with them."]);
        }
        if (($reason = $this->blockedReason($me, $target)) !== null) {
            throw ValidationException::withMessages(['attack' => $reason]);
        }
        if (!isset($me->weapons[$weaponId])) {
            throw ValidationException::withMessages(['attack' => 'You can only attack with a weapon you have equipped.']);
        }

        if ($me->attackingUserId !== $target->id) {
            if (!$this->combat->startFight($me->id, $target->id, $me->attackCost())) {
                throw ValidationException::withMessages(['attack' => 'You can only attack someone when you have 50% energy.']);
            }
            $this->session->put(self::SESSION_KEY, ['target' => $target->id, 'turn' => 1, 'log' => [], 'won' => null]);
        }

        $state = $this->session->get(self::SESSION_KEY);
        if (($state['target'] ?? null) !== $target->id) {
            $state = ['target' => $target->id, 'turn' => 1, 'log' => [], 'won' => null];
        }
        $lines = [];

        // Your hit.
        $weapon = $me->weapons[$weaponId];
        $turn = $state['turn'];
        if ($this->calculator->hits($me->agility, $target->agility)) {
            $damage = $this->calculator->weaponDamage($weapon['power'], $me->strength, $target->guard, $target->armor);
            $targetHp = $this->combat->damage($target->id, $damage);
            $lines[] = ['side' => 'you', 'text' => "{$turn}. Using {$me->pronoun()} {$weapon['name']} {$me->username} hit {$target->username} doing {$damage} damage ({$targetHp})"];
        } else {
            $targetHp = $target->hp;
            $lines[] = ['side' => 'you', 'text' => "{$turn}. {$me->username} tried to hit {$target->username} but missed ({$targetHp})"];
        }

        if ($targetHp <= 0) {
            $this->session->put(self::SESSION_KEY, ['target' => $target->id, 'turn' => $turn + 1, 'log' => [...$state['log'], ...$lines], 'won' => $target->id]);

            return ['result' => 'won', 'lines' => $lines, 'message' => null];
        }

        // Their hit back.
        $turn++;
        $myHp = $me->hp;
        if ($target->weapons === []) {
            $weaponName = 'fists';
            $damage = fn () => $this->calculator->fistDamage($target->strength, $me->guard, $me->armor);
        } else {
            $theirs = $target->weapons[$this->calculator->pick(array_keys($target->weapons))];
            $weaponName = $theirs['name'];
            $damage = fn () => $this->calculator->weaponDamage($theirs['power'], $target->strength, $me->guard, $me->armor);
        }
        if ($this->calculator->hits($target->agility, $me->agility)) {
            $dealt = $damage();
            $myHp = $this->combat->damage($me->id, $dealt);
            $lines[] = ['side' => 'them', 'text' => "{$turn}. Using {$target->pronoun()} {$weaponName} {$target->username} hit {$me->username} doing {$dealt} damage ({$myHp})"];
        } else {
            $lines[] = ['side' => 'them', 'text' => "{$turn}. {$target->username} tried to hit {$me->username} but missed ({$myHp})"];
        }

        $log = [...$state['log'], ...$lines];

        if ($myHp <= 0) {
            return ['result' => 'lost', 'lines' => $lines, 'message' => $this->lose($me, $target, $log)];
        }

        $this->session->put(self::SESSION_KEY, ['target' => $target->id, 'turn' => $turn + 1, 'log' => $log, 'won' => null]);

        return ['result' => 'continue', 'lines' => $lines, 'message' => null];
    }

    /** Give up a fight in progress; counts as a loss. */
    public function flee(Player $player, int $targetId): string
    {
        [$me, $target] = $this->fighters($player, $targetId);

        if ($me->attackingUserId !== $target->id || $this->awaitingChoice($target->id)) {
            throw ValidationException::withMessages(['attack' => "You aren't fighting {$target->username}."]);
        }

        return $this->lose($me, $target, [...$this->log($target->id), ['side' => 'you', 'text' => "{$me->username} ran away."]]);
    }

    /** Legacy attackwon.php / attackbeat.php / attacktake.php. */
    public function finish(Player $player, int $targetId, string $choice): string
    {
        if (!in_array($choice, self::CHOICES, true)) {
            throw new NotFoundException();
        }
        if (!$this->awaitingChoice($targetId)) {
            throw new AuthorizationException("Cheaters don't get anywhere.");
        }

        [$me, $target] = $this->fighters($player, $targetId);
        $log = $this->log($target->id);
        $this->session->forget(self::SESSION_KEY);

        return $this->db->transaction(function () use ($me, $target, $choice, $log): string {
            $this->combat->endFight($me->id);
            $beaten = $this->combat->lockBeaten($target->id)
                ?? throw ValidationException::withMessages(['attack' => "{$target->username} is no longer down."]);

            $now = $this->clock->now();
            $stolen = 0;
            $exp = 0;

            switch ($choice) {
                case 'mug':
                    $stolen = (int) round($beaten['money'] / ($this->random->int(200, 5000) / 10));
                    $minutes = $this->random->int(20, 40) + intdiv($me->level, 8);
                    $reason = "Mugged by {$me->username}";
                    $event = "{$me->username} mugged you and stole " . Money::of($stolen)->format() . '.';
                    $message = "You beat {$target->username}! You knock them on the floor a few times to make sure they're unconscious, then open their wallet, snatch " . Money::of($stolen)->format() . ', and run home happily.';
                    break;
                case 'hospitalize':
                    $minutes = $this->random->int(50, 150) + intdiv($me->level, 2);
                    $reason = "Hospitalized by {$me->username}";
                    $event = "{$me->username} beat you up.";
                    $message = "You beat {$target->username}! You beat them severely on the ground, then drop them off the roof of the nearest 10-story building, and run home silently and carefully.";
                    break;
                default:
                    $cube = $target->level ** 3;
                    $exp = $this->random->int(intdiv($cube, 2), $cube);
                    $minutes = $this->random->int(10, 20);
                    $reason = "Left by {$me->username}";
                    $event = "{$me->username} attacked you and left you lying outside the hospital.";
                    $percent = (int) ($exp / max(1, Player::expNeededFor($me->level)) * 100);
                    $message = "You beat {$target->username} and gained {$percent}% EXP! You hide your weapons and drop them off outside the hospital entrance. Feeling satisfied, you walk home.";
            }

            $this->combat->hospitalize($target->id, $now->modify("+{$minutes} minutes")->format('Y-m-d H:i:s'), $reason, $stolen);
            $this->combat->reward($me->id, $stolen, $exp);
            $this->events->add($target->id, $event);
            $this->combat->log([
                'attacker_id' => $me->id,
                'defender_id' => $target->id,
                'result' => 'won',
                'outcome' => match ($choice) { 'mug' => 'mugged', 'hospitalize' => 'hospitalized', default => 'left' },
                'money_stolen' => $stolen,
                'log' => $this->plainLog($log),
                'created_at' => $now->format('Y-m-d H:i:s'),
            ]);

            $message .= $this->gangRespect($target->gangId, $me->gangId, self::RESPECT[$choice], 'You earned %d respect for your gang!');

            if ($target->isNpc && ($bounty = $this->combat->claimBotReward($me->id, $target->id, $now->format('Y-m-d H:i:s'))) > 0) {
                $this->combat->reward($me->id, $bounty, 0);
                $message .= ' You gained ' . Money::of($bounty)->format() . " for beating the challenge bot {$target->username}.";
            }

            return $message;
        });
    }

    /** Legacy attacklost.php. */
    private function lose(Fighter $me, Fighter $target, array $log): string
    {
        $this->session->forget(self::SESSION_KEY);

        return $this->db->transaction(function () use ($me, $target, $log): string {
            $expLost = abs($me->level - $target->level) ** 3;
            $this->combat->endFight($me->id);
            $this->combat->loseExp($me->id, $expLost);
            $this->events->add($target->id, "{$me->username} attacked you and lost.");
            $this->combat->log([
                'attacker_id' => $me->id,
                'defender_id' => $target->id,
                'result' => 'lost',
                'outcome' => 'lost',
                'log' => $this->plainLog($log),
                'created_at' => $this->now(),
            ]);

            $percent = round($expLost / max(1, Player::expNeededFor($me->level)) * 100, 1);

            return "You lost to {$target->username} and lost {$percent}% EXP!"
                . $this->gangRespect($me->gangId, $target->gangId, self::RESPECT['lost'], 'You lost %d respect for your gang!');
        });
    }

    private function gangRespect(?int $loserGang, ?int $winnerGang, int $amount, string $message): string
    {
        if ($loserGang === null || $winnerGang === null || !$this->wars->atWar($loserGang, $winnerGang)) {
            return '';
        }

        $destroyed = $this->wars->transferRespect($loserGang, $winnerGang, $amount);

        return ' ' . sprintf($message, $amount) . ($destroyed ? ' The losing gang has run out of respect and was destroyed.' : '');
    }

    private function plainLog(array $log): string
    {
        return implode("\n", array_column($log, 'text'));
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
