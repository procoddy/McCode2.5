<?php

declare(strict_types=1);

namespace App\Modules\Players\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Players\Repositories\PlayerDirectoryRepository;
use App\Modules\Settings\Contracts\SettingsRepositoryInterface;
use App\Modules\Staff\Contracts\StaffRoleRepositoryInterface;
use App\Modules\Staff\Services\StaffPermissions;

/**
 * Legacy viewuser.php, plus reporting a player.
 */
final class ProfileController
{
    public function __construct(
        private readonly PlayerDirectoryRepository $directory,
        private readonly ClockInterface $clock,
    ) {
    }

    public function show(
        Request $request,
        int $id,
        StaffPermissions $permissions,
        StaffRoleRepositoryInterface $roles,
        SettingsRepositoryInterface $settings,
    ): Response
    {
        $profile = $this->directory->profile($id) ?? throw new NotFoundException('We could not find a player with that ID.');
        $viewer = $request->attribute('user');

        return view('pages.players.show', [
            'profile' => $profile,
            'viewer' => $request->attribute('player'),
            'staffRoles' => [
                ...($profile['role'] === 'admin' ? ['Owner'] : []),
                ...array_column($roles->rolesForUser($id), 'name'),
            ],
            'canEditPlayers' => $permissions->can($viewer, 'manage_users'),
            'canSeeIps' => $permissions->can($viewer, 'manage_punishments', 'manage_users'),
            'transfersOn' => (bool) $settings->get('sendbank_on', true),
            'now' => $this->clock->now()->format('Y-m-d H:i:s'),
            'onlineSince' => $this->clock->now()->modify('-' . PlayerDirectoryController::ONLINE_MINUTES . ' minutes')->format('Y-m-d H:i:s'),
        ]);
    }

    public function reportForm(int $id): Response
    {
        return view('pages.players.report', [
            'profile' => $this->directory->profile($id) ?? throw new NotFoundException(),
        ]);
    }

    public function report(Request $request, int $id, Validator $validator, Session $session): Response
    {
        $profile = $this->directory->profile($id) ?? throw new NotFoundException();

        if ($id === $request->attribute('user')->id) {
            throw ValidationException::withMessages(['body' => "You can't report yourself."]);
        }

        $data = $validator->validate(['body' => trim((string) $request->input('body', ''))], ['body' => 'required|string|min:10|max:2000']);
        $this->directory->addReport($request->attribute('user')->id, $id, $data['body'], $this->clock->now()->format('Y-m-d H:i:s'));
        $session->flash('status', "Thanks. Staff will look into your report about {$profile['username']}.");

        return Response::redirect("/players/{$id}");
    }
}
