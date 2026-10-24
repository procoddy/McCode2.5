<?php

declare(strict_types=1);

namespace App\Modules\Staff\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Modules\Staff\Contracts\StaffRoleRepositoryInterface;
use App\Modules\Staff\Services\StaffLog;
use App\Modules\Staff\Services\StaffPermissions;

/**
 * Legacy staff_roles.php.
 */
final class RoleController
{
    public function __construct(
        private readonly StaffRoleRepositoryInterface $roles,
        private readonly StaffLog $staffLog,
        private readonly Session $session,
    ) {
    }

    public function index(): Response
    {
        return view('pages.staff.roles.index', ['roles' => $this->roles->all()], 'admin');
    }

    public function create(): Response
    {
        return view('pages.staff.roles.form', ['role' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        $name = $this->validName($request, null);
        $this->roles->save(null, $name, array_fill_keys((array) $request->input('permissions', []), true));
        $this->staffLog->add("Created the staff role: {$name}");
        $this->session->flash('status', "Role {$name} created.");

        return Response::redirect('/admin/roles');
    }

    public function edit(int $id): Response
    {
        return view('pages.staff.roles.form', ['role' => $this->roles->find($id) ?? throw new NotFoundException()], 'admin');
    }

    public function update(Request $request, int $id): Response
    {
        $this->roles->find($id) ?? throw new NotFoundException();
        $name = $this->validName($request, $id);
        $this->roles->save($id, $name, array_fill_keys((array) $request->input('permissions', []), true));
        $this->staffLog->add("Edited the staff role: {$name}");
        $this->session->flash('status', "Role {$name} saved.");

        return Response::redirect('/admin/roles');
    }

    public function destroy(int $id): Response
    {
        $role = $this->roles->find($id) ?? throw new NotFoundException();
        $this->roles->delete($id);
        $this->staffLog->add("Deleted the staff role: {$role['name']}");
        $this->session->flash('status', "Role {$role['name']} deleted.");

        return Response::redirect('/admin/roles');
    }

    public function members(): Response
    {
        return view('pages.staff.roles.members', [
            'members' => $this->roles->members(),
            'roles' => $this->roles->all(),
        ], 'admin');
    }

    public function grant(Request $request, AuthRepositoryInterface $users): Response
    {
        [$user, $role] = $this->memberInput($request, $users);

        if (!$this->roles->grant($user->id, (int) $role['id'])) {
            throw ValidationException::withMessages(['username' => "{$user->username} already has the {$role['name']} role."]);
        }

        $this->staffLog->add("Granted staff role {$role['name']} to {$user->username} [{$user->id}]");
        $this->session->flash('status', "{$user->username} is now {$role['name']}.");

        return Response::redirect('/admin/roles/members');
    }

    public function revoke(Request $request, AuthRepositoryInterface $users): Response
    {
        [$user, $role] = $this->memberInput($request, $users);

        if (!$this->roles->revoke($user->id, (int) $role['id'])) {
            throw ValidationException::withMessages(['username' => "{$user->username} doesn't have the {$role['name']} role."]);
        }

        $this->staffLog->add("Revoked staff role {$role['name']} from {$user->username} [{$user->id}]");
        $this->session->flash('status', "{$role['name']} removed from {$user->username}.");

        return Response::redirect('/admin/roles/members');
    }

    private function validName(Request $request, ?int $id): string
    {
        $name = trim(strip_tags((string) $request->input('name', '')));

        if ($name === '' || mb_strlen($name) > 255) {
            throw ValidationException::withMessages(['name' => 'Enter a role name (up to 255 characters).']);
        }
        if ($this->roles->nameTaken($name, $id)) {
            throw ValidationException::withMessages(['name' => 'Another role already has that name.']);
        }

        return $name;
    }

    /** @return array{0: \App\Modules\Auth\Models\User, 1: array} */
    private function memberInput(Request $request, AuthRepositoryInterface $users): array
    {
        $user = $users->findByLogin(trim((string) $request->input('username', '')))
            ?? throw ValidationException::withMessages(['username' => 'No player with that username.']);
        $role = $this->roles->find((int) $request->input('role'))
            ?? throw ValidationException::withMessages(['role' => 'Choose a role.']);

        return [$user, $role];
    }
}
