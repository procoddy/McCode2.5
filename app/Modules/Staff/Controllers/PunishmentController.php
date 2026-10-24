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
use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Modules\Events\Services\EventService;
use App\Modules\Staff\Repositories\StaffUserRepository;
use App\Modules\Staff\Services\StaffLog;
use App\Modules\Staff\Services\StaffPermissions;

/**
 * Legacy staff_punit.php: mail and forum bans (mailform/forumform and their
 * un- versions), IP search and mass federal jail. Federal jail itself is
 * FederalJailController.
 */
final class PunishmentController
{
    /** URL segment => [column prefix, label] */
    private const BANS = ['mail' => ['mail', 'mail'], 'forum' => ['forum', 'the forums']];

    public function __construct(
        private readonly Database $db,
        private readonly Validator $validator,
        private readonly AuthRepositoryInterface $accounts,
        private readonly StaffPermissions $permissions,
        private readonly EventService $events,
        private readonly StaffLog $staffLog,
        private readonly Session $session,
        private readonly ClockInterface $clock,
    ) {
    }

    public function bans(): Response
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        return view('pages.staff.punishments.bans', [
            'mail' => $this->db->fetchAll('SELECT id, username, mail_banned_until AS until, mail_ban_reason AS reason FROM users WHERE mail_banned_until > ? ORDER BY mail_banned_until', [$now]),
            'forum' => $this->db->fetchAll('SELECT id, username, forum_banned_until AS until, forum_ban_reason AS reason FROM users WHERE forum_banned_until > ? ORDER BY forum_banned_until', [$now]),
        ], 'admin');
    }

    public function ban(Request $request, string $type): Response
    {
        [$column, $label] = self::BANS[$type] ?? throw new NotFoundException();
        $data = $this->validator->validate($request->all(), [
            'username' => 'required|string',
            'days' => 'required|integer|min:1|max:3650',
            'reason' => 'required|string|max:255',
        ]);
        $target = $this->accounts->findByLogin(trim((string) $data['username']))
            ?? throw ValidationException::withMessages(['username' => 'No player with that username.']);

        if ($this->permissions->can($target, 'administrator')) {
            throw ValidationException::withMessages(['username' => "Administrators can't be banned. Remove their staff role first."]);
        }

        $days = (int) $data['days'];
        $reason = trim($data['reason']);
        $this->db->update('users', [
            "{$column}_banned_until" => $this->clock->now()->modify("+{$days} days")->format('Y-m-d H:i:s'),
            "{$column}_ban_reason" => $reason,
        ], ['id' => $target->id]);
        $this->events->add($target->id, "You were banned from {$label} for {$days} day(s) for the following reason: {$reason}");
        $this->staffLog->add(ucfirst($type) . " banned {$target->username} [{$target->id}] for {$days} days");

        return $this->done('/admin/bans', "{$target->username} is banned from {$label} for {$days} days.");
    }

    public function unban(int $id, string $type): Response
    {
        [$column, $label] = self::BANS[$type] ?? throw new NotFoundException();
        $user = $this->db->fetch('SELECT id, username FROM users WHERE id = ?', [$id]) ?? throw new NotFoundException();

        $this->db->update('users', ["{$column}_banned_until" => null, "{$column}_ban_reason" => null], ['id' => $id]);
        $this->events->add($id, "You were unbanned from {$label}.");
        $this->staffLog->add("Removed {$user['username']} [{$id}]'s {$type} ban");

        return $this->done('/admin/bans', "{$user['username']} was unbanned from {$label}.");
    }

    public function ipSearch(Request $request): Response
    {
        $ip = trim((string) $request->query('ip', ''));
        $valid = $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false;

        return view('pages.staff.punishments.ip-search', [
            'ip' => $ip,
            'invalid' => $ip !== '' && !$valid,
            'users' => $valid ? $this->db->fetchAll(
                'SELECT id, username, level, money, last_login_ip, signup_ip FROM users WHERE last_login_ip = ? OR signup_ip = ? ORDER BY id',
                [$ip, $ip],
            ) : [],
        ], 'admin');
    }

    /** Legacy massjailip: federal jail every selected account (from an IP search). */
    public function massJail(Request $request, StaffUserRepository $users): Response
    {
        $data = $this->validator->validate($request->all(), [
            'ids' => 'required',
            'days' => 'required|integer|min:1|max:3650',
            'reason' => 'required|string|max:1000',
        ]);
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $data['ids']))));
        $now = $this->clock->now();
        $days = (int) $data['days'];
        $jailed = [];

        foreach ($ids as $id) {
            $target = $this->accounts->findById($id);
            if ($target === null || $this->permissions->can($target, 'administrator')) {
                continue;
            }
            $users->jail($id, $request->attribute('user')->id, $days, trim($data['reason']), $now->modify("+{$days} days")->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s'));
            $users->forceLogout($id);
            $jailed[] = "{$target->username} [{$id}]";
        }

        if ($jailed === []) {
            throw ValidationException::withMessages(['ids' => 'Nobody was jailed (administrators are skipped).']);
        }

        $this->staffLog->add("Mass federally jailed for {$days} days: " . implode(', ', $jailed));

        return $this->done('/admin/fedjail', 'Jailed ' . count($jailed) . ' players.');
    }

    private function done(string $url, string $message): Response
    {
        $this->session->flash('status', $message);

        return Response::redirect($url);
    }
}
