<?php

declare(strict_types=1);

namespace App\Modules\Staff\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Events\Services\EventService;
use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Staff\Repositories\StaffUserRepository;
use App\Modules\Staff\Services\StaffLog;
use App\Support\Money;

/**
 * Legacy staff_users.php and staffnotes.php.
 */
final class UserController
{
    public function __construct(
        private readonly StaffUserRepository $users,
        private readonly Validator $validator,
        private readonly StaffLog $staffLog,
        private readonly Session $session,
        private readonly ClockInterface $clock,
    ) {
    }

    public function index(Request $request): Response
    {
        $query = (string) $request->query('q', '');

        return view('pages.staff.users.index', [
            'query' => $query,
            'users' => $this->users->search($query, (int) $request->query('page', 1)),
        ], 'admin');
    }

    public function edit(int $id): Response
    {
        return view('pages.staff.users.edit', [
            'player' => $this->users->find($id) ?? throw new NotFoundException('Player not found.'),
            'cities' => app(\App\Modules\Staff\Resources\ResourceRepository::class)->options('cities'),
            'houses' => app(\App\Modules\Staff\Resources\ResourceRepository::class)->options('houses'),
        ], 'admin');
    }

    public function update(Request $request, int $id): Response
    {
        $player = $this->users->find($id) ?? throw new NotFoundException('Player not found.');
        $int = 'required|integer|min:0';
        $data = $this->validator->validate($request->all(), [
            'username' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9_]+$/'],
            'email' => 'required|email|max:255',
            'level' => 'required|integer|min:1',
            'money' => $int, 'crystals' => $int,
            'bank_balance' => 'integer|min:0', 'cyber_balance' => 'integer|min:0',
            'max_energy' => 'required|integer|min:1', 'max_brave' => 'required|integer|min:1',
            'max_will' => 'required|integer|min:1', 'max_hp' => 'required|integer|min:1',
            'strength' => 'required|numeric|min:0', 'agility' => 'required|numeric|min:0', 'guard' => 'required|numeric|min:0',
            'labour' => 'required|numeric|min:0', 'iq' => 'required|numeric|min:0',
            'city_id' => 'required|integer', 'house_id' => 'required|integer',
            'gender' => 'required|in:Male,Female',
            'account_type' => 'required|in:Demo,Active,Staff,NPC',
            'donator_days' => $int, 'jail_minutes' => $int, 'hospital_minutes' => $int,
            'jail_reason' => 'string|max:255', 'hospital_reason' => 'string|max:255',
            'duties' => 'string|max:255', 'staff_notes' => 'string|max:5000',
        ]);

        $errors = [];
        foreach (['username', 'email'] as $unique) {
            if ($this->users->valueTaken($unique, (string) $data[$unique], $id)) {
                $errors[$unique] = ["Another player already uses this {$unique}."];
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $now = $this->clock->now();
        $until = fn (int $minutes) => $minutes > 0 ? $now->modify("+{$minutes} minutes")->format('Y-m-d H:i:s') : null;
        $nullableInt = fn (?string $v) => $v === null || $v === '' ? null : (int) $v;

        $this->users->update($id, [
            'username' => $data['username'],
            'email' => strtolower((string) $data['email']),
            'level' => (int) $data['level'],
            'money' => (int) $data['money'],
            'crystals' => (int) $data['crystals'],
            'bank_balance' => $nullableInt($data['bank_balance'] ?? null),
            'cyber_balance' => $nullableInt($data['cyber_balance'] ?? null),
            'max_energy' => (int) $data['max_energy'],
            'max_brave' => (int) $data['max_brave'],
            'max_will' => (int) $data['max_will'],
            'max_hp' => (int) $data['max_hp'],
            'city_id' => (int) $data['city_id'],
            'house_id' => (int) $data['house_id'],
            'gender' => $data['gender'],
            'account_type' => $data['account_type'],
            'donator_until' => (int) $data['donator_days'] > 0 ? $now->modify("+{$data['donator_days']} days")->format('Y-m-d H:i:s') : null,
            'jail_until' => $until((int) $data['jail_minutes']),
            'jail_reason' => (string) ($data['jail_reason'] ?? ''),
            'hospital_until' => $until((int) $data['hospital_minutes']),
            'hospital_reason' => (string) ($data['hospital_reason'] ?? ''),
            'duties' => (string) ($data['duties'] ?? '') ?: 'N/A',
        ], array_map('floatval', array_intersect_key($data, array_flip(StaffUserRepository::STAT_COLUMNS))));

        if (($data['staff_notes'] ?? '') !== (string) $player['staff_notes']) {
            $this->users->updateNotes($id, (string) ($data['staff_notes'] ?? ''), $request->attribute('user')->id, $now->format('Y-m-d H:i:s'));
        }

        $this->staffLog->add("Edited player {$data['username']} [{$id}]");
        $this->session->flash('status', "{$data['username']} saved.");

        return Response::redirect("/admin/users/{$id}/edit");
    }

    public function destroy(Request $request, int $id): Response
    {
        $player = $this->users->find($id) ?? throw new NotFoundException('Player not found.');

        if ($id === 1 || $id === $request->attribute('user')->id) {
            $this->session->flash('status', 'That account cannot be deleted.');

            return Response::redirect("/admin/users/{$id}/edit");
        }

        $this->users->delete($id);
        $this->staffLog->add("Deleted player {$player['username']} [{$id}]");
        $this->session->flash('status', "{$player['username']} was deleted.");

        return Response::redirect('/admin/users');
    }

    public function forceLogout(int $id): Response
    {
        $player = $this->users->find($id) ?? throw new NotFoundException('Player not found.');
        $this->users->forceLogout($id);
        $this->staffLog->add("Forced {$player['username']} [{$id}] to log out");
        $this->session->flash('status', "{$player['username']} will be logged out on their next page load.");

        return Response::redirect("/admin/users/{$id}/edit");
    }

    public function saveNotes(Request $request, int $id): Response
    {
        $player = $this->users->find($id) ?? throw new NotFoundException('Player not found.');
        $notes = mb_substr(trim((string) $request->input('staff_notes', '')), 0, 5000);
        $this->users->updateNotes($id, $notes, $request->attribute('user')->id, $this->clock->now()->format('Y-m-d H:i:s'));
        $this->staffLog->add("Updated staff notes for {$player['username']} [{$id}]");
        $this->session->flash('status', 'Staff notes saved.');

        return Response::redirect("/players/{$id}");
    }

    public function creditForm(): Response
    {
        return view('pages.staff.users.credit', ['mass' => false], 'admin');
    }

    public function credit(Request $request, EventService $events): Response
    {
        $data = $this->validator->validate($request->all(), [
            'username' => 'required|string',
            'money' => 'required|integer|min:0',
            'crystals' => 'required|integer|min:0',
        ]);
        $player = $this->users->findByUsername(trim((string) $data['username']))
            ?? throw ValidationException::withMessages(['username' => 'No player with that username.']);
        [$money, $crystals] = $this->amounts($data);

        $this->users->credit((int) $player['id'], $money, $crystals);
        $events->add((int) $player['id'], 'You were credited ' . Money::of($money)->format() . " and {$crystals} crystals by staff.");
        $this->staffLog->add("Credited {$player['username']} [{$player['id']}] " . Money::of($money)->format() . " and {$crystals} crystals");
        $this->session->flash('status', "{$player['username']} was credited.");

        return Response::redirect('/admin/users/credit');
    }

    /** Legacy staff_items.php?action=giveitem. */
    public function giveItemForm(Database $db): Response
    {
        return view('pages.staff.users.give-item', [
            'items' => array_column($db->fetchAll('SELECT id, name FROM items ORDER BY name'), 'name', 'id'),
        ], 'admin');
    }

    public function giveItem(Request $request, InventoryRepository $inventory, EventService $events): Response
    {
        $data = $this->validator->validate($request->all(), [
            'username' => 'required|string',
            'item_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1|max:1000000',
        ]);
        $player = $this->users->findByUsername(trim((string) $data['username']))
            ?? throw ValidationException::withMessages(['username' => 'No player with that username.']);
        $item = $inventory->findItem((int) $data['item_id'])
            ?? throw ValidationException::withMessages(['item_id' => 'Choose an item from the list.']);
        $quantity = (int) $data['quantity'];

        $inventory->add((int) $player['id'], $item->id, $quantity);
        $events->add((int) $player['id'], "You were given {$quantity} {$item->name} by staff.");
        $this->staffLog->add("Gave {$quantity} {$item->name} [{$item->id}] to {$player['username']} [{$player['id']}]");
        $this->session->flash('status', "{$player['username']} was given {$quantity} {$item->name}.");

        return Response::redirect('/admin/users/give-item');
    }

    /** Legacy staff_users.php inventory viewer. */
    public function inventory(int $id, InventoryRepository $inventory): Response
    {
        $player = $this->users->find($id) ?? throw new NotFoundException();
        $this->staffLog->add("Viewed {$player['username']} [{$id}]'s inventory");

        return view('pages.staff.users.inventory', [
            'player' => $player,
            'entries' => $inventory->inventory($id),
            'equipped' => $inventory->equipped($id),
        ], 'admin');
    }

    public function removeInventoryItem(Request $request, int $id, int $item, InventoryRepository $inventory): Response
    {
        $player = $this->users->find($id) ?? throw new NotFoundException();
        $quantity = (int) $this->validator->validate($request->all(), ['quantity' => 'required|integer|min:1'])['quantity'];
        $held = $inventory->quantity($id, $item);

        if ($held === 0 || !$inventory->remove($id, $item, min($quantity, $held))) {
            throw ValidationException::withMessages(['quantity' => 'The player does not have that item.']);
        }

        $this->staffLog->add('Removed ' . min($quantity, $held) . " of item #{$item} from {$player['username']} [{$id}]'s inventory");
        $this->session->flash('status', 'Item removed from the inventory.');

        return Response::redirect("/admin/users/{$id}/inventory");
    }

    public function massCreditForm(): Response
    {
        return view('pages.staff.users.credit', ['mass' => true], 'admin');
    }

    public function massCredit(Request $request): Response
    {
        [$money, $crystals] = $this->amounts($this->validator->validate($request->all(), [
            'money' => 'required|integer|min:0',
            'crystals' => 'required|integer|min:0',
        ]));
        $count = $this->users->creditAll($money, $crystals);

        $this->staffLog->add('Credited all players ' . Money::of($money)->format() . " and {$crystals} crystals");
        $this->session->flash('status', "{$count} players credited. Consider posting an announcement explaining why.");

        return Response::redirect('/admin/users/mass-credit');
    }

    public function reports(): Response
    {
        return view('pages.staff.users.reports', ['reports' => $this->users->reports()], 'admin');
    }

    public function clearReport(int $id): Response
    {
        if ($this->users->deleteReport($id)) {
            $this->staffLog->add("Cleared player report #{$id}");
        }
        $this->session->flash('status', 'Report cleared.');

        return Response::redirect('/admin/reports');
    }

    /** @return array{0: int, 1: int} */
    private function amounts(array $data): array
    {
        $money = (int) $data['money'];
        $crystals = (int) $data['crystals'];

        if ($money === 0 && $crystals === 0) {
            throw ValidationException::withMessages(['money' => 'Enter an amount of money and/or crystals.']);
        }

        return [$money, $crystals];
    }
}
