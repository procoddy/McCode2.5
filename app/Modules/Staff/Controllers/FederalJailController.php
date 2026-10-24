<?php

declare(strict_types=1);

namespace App\Modules\Staff\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Modules\Events\Services\EventService;
use App\Modules\Staff\Repositories\StaffUserRepository;
use App\Modules\Staff\Services\StaffLog;
use App\Modules\Staff\Services\StaffPermissions;

/**
 * Legacy staff_punit.php (federal jail). A federally jailed player sees only
 * the "you are in federal jail" page until released or the time runs out.
 */
final class FederalJailController
{
    public function __construct(
        private readonly StaffUserRepository $users,
        private readonly StaffLog $staffLog,
        private readonly Session $session,
        private readonly ClockInterface $clock,
    ) {
    }

    public function index(): Response
    {
        return view('pages.staff.users.fedjail', ['jailed' => $this->users->federalJail()], 'admin');
    }

    public function jail(
        Request $request,
        Validator $validator,
        AuthRepositoryInterface $accounts,
        StaffPermissions $permissions,
    ): Response {
        $data = $validator->validate($request->all(), [
            'username' => 'required|string',
            'days' => 'required|integer|min:1|max:3650',
            'reason' => 'required|string|max:1000',
        ]);
        $target = $accounts->findByLogin(trim((string) $data['username']))
            ?? throw ValidationException::withMessages(['username' => 'No player with that username.']);

        if ($permissions->can($target, 'administrator')) {
            throw ValidationException::withMessages(['username' => 'Administrators cannot be jailed. Remove their staff role first.']);
        }

        $now = $this->clock->now();
        $days = (int) $data['days'];
        $this->users->jail(
            $target->id,
            $request->attribute('user')->id,
            $days,
            trim((string) $data['reason']),
            $now->modify("+{$days} days")->format('Y-m-d H:i:s'),
            $now->format('Y-m-d H:i:s'),
        );
        $this->users->forceLogout($target->id);

        $this->staffLog->add("Federally jailed {$target->username} [{$target->id}] for {$days} days: {$data['reason']}");
        $this->session->flash('status', "{$target->username} is in federal jail for {$days} days.");

        return Response::redirect('/admin/fedjail');
    }

    public function release(Request $request, int $id, EventService $events): Response
    {
        if ($this->users->release($id, $request->attribute('user')->id, $this->clock->now()->format('Y-m-d H:i:s'))) {
            $events->add($id, 'You were released from federal jail.');
            $this->staffLog->add("Released user #{$id} from federal jail");
            $this->session->flash('status', 'Player released.');
        }

        return Response::redirect('/admin/fedjail');
    }
}
