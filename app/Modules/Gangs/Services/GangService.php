<?php

declare(strict_types=1);

namespace App\Modules\Gangs\Services;

use App\Contracts\ClockInterface;
use App\Contracts\RandomInterface;
use App\Core\Database;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Events\Services\EventService;
use App\Modules\Gangs\Repositories\GangRepository;
use App\Modules\Mail\Services\MailService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use App\Support\Money;

/**
 * Legacy creategang.php and yourgang.php. "Leaders" are the president and
 * vice-president; some actions are president-only, as in the legacy menu.
 * Each action returns the message to show.
 */
final class GangService
{
    public function __construct(
        private readonly GangRepository $gangs,
        private readonly PlayerRepositoryInterface $players,
        private readonly EventService $events,
        private readonly MailService $mail,
        private readonly Database $db,
        private readonly RandomInterface $random,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * The player's gang and their rank in it.
     *
     * @return array{gang: array, isPresident: bool, isLeader: bool}
     */
    public function membership(Player $player): array
    {
        $gang = $player->gangId === null ? null : $this->gangs->find($player->gangId);

        if ($gang === null) {
            throw new NotFoundException("You're not in a gang.");
        }

        $isPresident = (int) $gang['president_id'] === $player->id;

        return [
            'gang' => $gang,
            'isPresident' => $isPresident,
            'isLeader' => $isPresident || (int) $gang['vice_president_id'] === $player->id,
        ];
    }

    public function create(Player $player, string $name, string $description): string
    {
        $cost = (int) config('game.gangs.create_cost');

        if ($player->gangId !== null) {
            throw ValidationException::withMessages(['name' => "You're already in a gang!"]);
        }
        if ($this->gangs->nameTaken($name)) {
            throw ValidationException::withMessages(['name' => 'Another gang already has that name.']);
        }

        return $this->db->transaction(function () use ($player, $name, $description, $cost): string {
            if (!$this->players->changeCurrency($player->id, 'money', -$cost)) {
                throw ValidationException::withMessages(['name' => "You don't have enough money. You need " . Money::of($cost)->format() . '.']);
            }

            $gangId = $this->gangs->create([
                'name' => $name,
                'description' => $description,
                'respect' => (int) config('game.gangs.starting_respect'),
                'capacity' => (int) config('game.gangs.starting_capacity'),
                'president_id' => $player->id,
                'vice_president_id' => $player->id,
                'created_at' => $this->now(),
            ]);

            if (!$this->gangs->join($player->id, $gangId, $this->now())) {
                throw ValidationException::withMessages(['name' => "You're already in a gang!"]);
            }
            $this->gangs->deleteApplicationsOf($player->id);
            $this->gangs->addEvent($gangId, "{$player->username} founded the gang.", $this->now());

            return 'Gang created!';
        });
    }

    public function apply(Player $player, int $gangId, string $body): string
    {
        $gang = $this->gangs->find($gangId) ?? throw new NotFoundException('That gang does not exist.');

        if ($player->gangId !== null) {
            throw ValidationException::withMessages(['body' => "You're already in a gang."]);
        }
        if (!$this->gangs->apply($gangId, $player->id, $body, $this->now())) {
            throw ValidationException::withMessages(['body' => "You've already applied to {$gang['name']}."]);
        }

        $this->gangs->addEvent($gangId, "{$player->username} sent an application to join this gang.", $this->now());

        return "Your application to {$gang['name']} was sent.";
    }

    public function donate(Player $player, int $money, int $crystals): string
    {
        $gang = $this->membership($player)['gang'];

        if ($money === 0 && $crystals === 0) {
            throw ValidationException::withMessages(['money' => 'Enter some money or crystals to donate.']);
        }

        $this->db->transaction(function () use ($player, $gang, $money, $crystals): void {
            if (($money > 0 && !$this->players->changeCurrency($player->id, 'money', -$money))
                || ($crystals > 0 && !$this->players->changeCurrency($player->id, 'crystals', -$crystals))) {
                throw ValidationException::withMessages(['money' => "You can't afford that donation."]);
            }

            $this->gangs->deposit((int) $gang['id'], $money, $crystals);
            $this->gangs->addEvent((int) $gang['id'], "{$player->username} donated " . Money::of($money)->format() . ' and ' . number_format($crystals) . ' crystals to the gang.', $this->now());
        });

        return 'You donated ' . Money::of($money)->format() . ' and ' . number_format($crystals) . ' crystals to the gang.';
    }

    public function leave(Player $player): string
    {
        ['gang' => $gang, 'isLeader' => $isLeader] = $this->membership($player);

        if ($isLeader) {
            throw ValidationException::withMessages(['gang' => "You can't leave while you're the president or vice-president. Hand the role to someone else first."]);
        }

        $this->gangs->leave($player->id, (int) $gang['id']);
        $this->gangs->addEvent((int) $gang['id'], "{$player->username} left the gang.", $this->now());

        return "You left {$gang['name']}.";
    }

    public function kick(Player $leader, int $userId): string
    {
        $gang = $this->leader($leader)['gang'];

        if ($userId === (int) $gang['president_id']) {
            throw ValidationException::withMessages(['gang' => 'The president cannot be kicked.']);
        }
        if ($userId === $leader->id) {
            throw ValidationException::withMessages(['gang' => "You can't kick yourself. Hand your role to someone else, then leave."]);
        }

        $member = $this->players->find($userId);
        if ($member === null || !$this->gangs->leave($userId, (int) $gang['id'])) {
            throw ValidationException::withMessages(['gang' => 'That player is not in your gang.']);
        }

        if ((int) $gang['vice_president_id'] === $userId) {
            $this->gangs->update((int) $gang['id'], ['vice_president_id' => $gang['president_id']]);
        }
        $this->events->add($userId, "You were kicked out of {$gang['name']} by {$leader->username}.");
        $this->gangs->addEvent((int) $gang['id'], "{$member->username} was kicked out of the gang by {$leader->username}.", $this->now());

        return "{$member->username} was kicked from the gang.";
    }

    public function decideApplication(Player $leader, int $applicationId, bool $accept): string
    {
        $gang = $this->leader($leader)['gang'];
        $application = $this->gangs->application($applicationId, (int) $gang['id'])
            ?? throw new NotFoundException('That application does not exist.');

        return $this->db->transaction(function () use ($leader, $gang, $application, $accept): string {
            $this->gangs->deleteApplication((int) $application['id']);
            $userId = (int) $application['user_id'];

            if (!$accept) {
                $this->events->add($userId, "Your application to join {$gang['name']} was declined.");
                $this->gangs->addEvent((int) $gang['id'], "{$leader->username} declined {$application['username']}'s application.", $this->now());

                return "You declined {$application['username']}'s application.";
            }

            if ((int) $gang['member_count'] >= (int) $gang['capacity']) {
                throw ValidationException::withMessages(['gang' => 'Your gang is full. Upgrade it to hold more before you accept another member.']);
            }
            if (!$this->gangs->join($userId, (int) $gang['id'], $this->now())) {
                throw ValidationException::withMessages(['gang' => "{$application['username']} has already joined another gang."]);
            }

            $this->gangs->deleteApplicationsOf($userId);
            $this->events->add($userId, "Your application to join {$gang['name']} was accepted. Congratulations!");
            $this->gangs->addEvent((int) $gang['id'], "{$leader->username} accepted {$application['username']}'s application.", $this->now());

            return "{$application['username']} joined the gang.";
        });
    }

    public function giveFromVault(Player $leader, int $userId, int $money, int $crystals): string
    {
        $gang = $this->leader($leader)['gang'];

        if ($money === 0 && $crystals === 0) {
            throw ValidationException::withMessages(['money' => 'Enter some money or crystals to give.']);
        }
        if (!$this->gangs->isMember((int) $gang['id'], $userId)) {
            throw ValidationException::withMessages(['user_id' => 'That player is not in your gang.']);
        }

        $member = $this->players->find($userId);

        $this->db->transaction(function () use ($gang, $userId, $money, $crystals, $member, $leader): void {
            if (!$this->gangs->withdraw((int) $gang['id'], $money, $crystals)) {
                throw ValidationException::withMessages(['money' => "The vault doesn't hold that much."]);
            }

            $this->players->changeCurrency($userId, 'money', $money);
            $this->players->changeCurrency($userId, 'crystals', $crystals);
            $what = Money::of($money)->format() . ' and ' . number_format($crystals) . ' crystals';
            $this->events->add($userId, "You were given {$what} from your gang's vault.");
            $this->gangs->addEvent((int) $gang['id'], "{$member->username} was given {$what} from the vault by {$leader->username}.", $this->now());
        });

        return "You gave {$member->username} money from the vault.";
    }

    public function massPayment(Player $leader, int $amount): string
    {
        $gang = $this->leader($leader)['gang'];
        $paid = $this->gangs->payMembers((int) $gang['id'], $amount);

        if ($paid === 0) {
            throw ValidationException::withMessages(['amount' => "The vault doesn't have enough to pay every member that much."]);
        }

        foreach ($this->gangs->members((int) $gang['id']) as $member) {
            $this->events->add((int) $member['id'], 'You were given ' . Money::of($amount)->format() . " from your gang's mass payment.");
        }
        $this->gangs->addEvent((int) $gang['id'], "{$leader->username} paid every member " . Money::of($amount)->format() . '.', $this->now());

        return "You paid {$paid} members " . Money::of($amount)->format() . ' each.';
    }

    public function setVicePresident(Player $leader, int $userId): string
    {
        return $this->setRole($this->leader($leader)['gang'], $leader, $userId, 'vice_president_id', 'vice-president');
    }

    public function setPresident(Player $president, int $userId): string
    {
        return $this->setRole($this->president($president)['gang'], $president, $userId, 'president_id', 'president');
    }

    public function upgradeCapacity(Player $leader, int $slots): string
    {
        $gang = $this->leader($leader)['gang'];
        $cost = $slots * (int) config('game.gangs.capacity_slot_cost');

        if (!$this->gangs->buyCapacity((int) $gang['id'], $slots, $cost)) {
            throw ValidationException::withMessages(['slots' => 'Your gang does not have enough money to upgrade that much capacity.']);
        }

        return 'You paid ' . Money::of($cost)->format() . " to add {$slots} capacity to your gang.";
    }

    public function planCrime(Player $leader, int $crimeId): string
    {
        $gang = $this->leader($leader)['gang'];
        $crime = $this->gangs->organisedCrime($crimeId) ?? throw ValidationException::withMessages(['crime_id' => 'That crime does not exist.']);

        if ((int) $crime['members_required'] > (int) $gang['member_count']) {
            throw ValidationException::withMessages(['crime_id' => "You need {$crime['members_required']} members for that crime."]);
        }

        $readyAt = $this->clock->now()->modify('+' . (int) config('game.gangs.organised_crime_hours') . ' hours')->format('Y-m-d H:i:s');
        if (!$this->gangs->planCrime((int) $gang['id'], (int) $crime['id'], $readyAt)) {
            throw ValidationException::withMessages(['crime_id' => 'Your gang is already planning a crime.']);
        }

        $this->gangs->addEvent((int) $gang['id'], "{$leader->username} started planning {$crime['name']}.", $this->now());

        return "You started planning {$crime['name']}. It will happen in " . config('game.gangs.organised_crime_hours') . ' hours.';
    }

    public function setAnnouncement(Player $leader, string $text): string
    {
        $this->gangs->update((int) $this->leader($leader)['gang']['id'], ['announcement' => $text]);

        return 'Announcement changed.';
    }

    public function updateDetails(Player $president, string $name, string $description, string $prefix): string
    {
        $gang = $this->president($president)['gang'];

        if ($this->gangs->nameTaken($name, (int) $gang['id'])) {
            throw ValidationException::withMessages(['name' => 'Another gang already has that name.']);
        }

        $this->gangs->update((int) $gang['id'], ['name' => $name, 'description' => $description, 'prefix' => $prefix]);

        return 'Gang details saved.';
    }

    public function massMail(Player $president, string $subject, string $body): string
    {
        $gang = $this->president($president)['gang'];
        $count = $this->mail->sendToGang($president->id, (int) $gang['id'], $subject, $body);

        return "Mass mail sent to {$count} members.";
    }

    public function declareWar(Player $president, int $enemyId): string
    {
        $gang = $this->president($president)['gang'];
        $enemy = $this->gangs->find($enemyId) ?? throw ValidationException::withMessages(['gang_id' => 'That gang does not exist.']);

        if ($enemyId === (int) $gang['id']) {
            throw ValidationException::withMessages(['gang_id' => "You can't declare war on your own gang."]);
        }
        if (!$this->gangs->declareWar((int) $gang['id'], $enemyId, $this->now())) {
            throw ValidationException::withMessages(['gang_id' => "You're already at war with {$enemy['name']}."]);
        }

        $this->gangs->addEvent((int) $gang['id'], "Your gang declared war on {$enemy['name']}.", $this->now());
        $this->gangs->addEvent($enemyId, "{$gang['name']} declared war on your gang.", $this->now());

        return "You have declared war on {$enemy['name']}!";
    }

    public function offerSurrender(Player $president, int $warId, string $message): string
    {
        $gang = $this->president($president)['gang'];
        $war = $this->gangs->war($warId, (int) $gang['id']) ?? throw ValidationException::withMessages(['war_id' => 'That war does not exist.']);
        $enemyId = (int) $war['enemy_id'];

        $this->gangs->offerSurrender($warId, (int) $gang['id'], $enemyId, $message, $this->now());
        $this->gangs->addEvent($enemyId, "{$gang['name']} has asked to surrender.", $this->now());

        return 'Your surrender offer was sent.';
    }

    public function acceptSurrender(Player $president, int $surrenderId): string
    {
        $gang = $this->president($president)['gang'];
        $surrender = $this->gangs->surrender($surrenderId, (int) $gang['id'])
            ?? throw new NotFoundException('That surrender does not exist.');
        $enemy = $this->gangs->find((int) $surrender['from_gang_id']);

        $this->gangs->endWar((int) $surrender['war_id']);
        $this->gangs->addEvent((int) $gang['id'], "Your gang accepted {$enemy['name']}'s surrender. The war is over.", $this->now());
        $this->gangs->addEvent((int) $surrender['from_gang_id'], "{$gang['name']} accepted your surrender. The war is over.", $this->now());

        return "You accepted the surrender. The war with {$enemy['name']} is over.";
    }

    /** Legacy gang forum link: makes sure the gang's forum exists and returns its id. */
    public function forumId(Player $player): int
    {
        $gang = $this->membership($player)['gang'];

        return $this->gangs->forumId((int) $gang['id'], $gang['name']);
    }

    /** Cron (legacy cron_hour.php): carry out organised crimes whose planning time is up. */
    public function runReadyCrimes(): int
    {
        $count = 0;

        foreach ($this->gangs->readyCrimes($this->now()) as $crime) {
            $gangId = (int) $crime['gang_id'];
            $success = $this->random->int(0, 1) === 1;
            $money = $success ? $this->random->int((int) $crime['min_money'], max((int) $crime['min_money'], (int) $crime['max_money'])) : 0;
            $log = trim(($crime['start_text'] ?? '') . "\n" . str_replace('{muny}', Money::of($money)->format(), (string) ($success ? $crime['success_text'] : $crime['failure_text'])));

            if ($this->gangs->completeCrime($gangId, $crime, $success, $money, $log, $this->now()) === null) {
                continue;
            }

            $text = "Your gang's organised crime ({$crime['name']}) " . ($success ? 'succeeded, earning ' . Money::of($money)->format() . '.' : 'failed.');
            foreach ($this->gangs->members($gangId) as $member) {
                $this->events->add((int) $member['id'], $text);
            }
            $this->gangs->addEvent($gangId, $text, $this->now());
            $count++;
        }

        return $count;
    }

    private function setRole(array $gang, Player $by, int $userId, string $column, string $label): string
    {
        if (!$this->gangs->isMember((int) $gang['id'], $userId)) {
            throw ValidationException::withMessages(['user_id' => 'That player is not in your gang.']);
        }

        $member = $this->players->find($userId);
        $this->gangs->update((int) $gang['id'], [$column => $userId]);
        $this->events->add($userId, "You were made {$label} of {$gang['name']}.");
        $this->gangs->addEvent((int) $gang['id'], "{$by->username} made {$member->username} {$label}.", $this->now());

        return "{$member->username} is now {$label}.";
    }

    private function leader(Player $player): array
    {
        $membership = $this->membership($player);

        if (!$membership['isLeader']) {
            throw new AuthorizationException('Only the president and vice-president can do that.');
        }

        return $membership;
    }

    private function president(Player $player): array
    {
        $membership = $this->membership($player);

        if (!$membership['isPresident']) {
            throw new AuthorizationException('Only the president can do that.');
        }

        return $membership;
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
