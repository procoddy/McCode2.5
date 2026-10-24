<?php

declare(strict_types=1);

namespace App\Modules\Staff\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Staff\Contracts\StaffRoleRepositoryInterface;

/**
 * Legacy check_access() / is_staff(). Permissions come from the staff roles a
 * user holds; the `administrator` permission and users.role = 'admin' (the
 * game owner) grant everything.
 */
final class StaffPermissions
{
    public const ALL = [
        'administrator', 'credit_all_users', 'credit_item', 'credit_user', 'edit_newspaper',
        'manage_challenge_bots', 'manage_cities', 'manage_courses', 'manage_crimes', 'manage_donator_packs',
        'manage_forums', 'manage_gangs', 'manage_houses', 'manage_items', 'manage_jobs', 'manage_player_reports',
        'manage_polls', 'manage_punishments', 'manage_roles', 'manage_shops', 'manage_staff', 'manage_users',
        'mass_mail', 'use_staff_forums', 'view_logs', 'view_user_inventory',
    ];

    /** @var array<int, array{roles: list<string>, permissions: list<string>}> */
    private array $cache = [];

    public function __construct(private readonly StaffRoleRepositoryInterface $roles)
    {
    }

    public function isStaff(User $user): bool
    {
        return $user->isAdmin() || $this->load($user)['roles'] !== [];
    }

    /** True if the user has at least one of the given permissions. */
    public function can(User $user, string ...$permissions): bool
    {
        $granted = $this->load($user)['permissions'];

        foreach ($permissions as $permission) {
            if (in_array($permission, $granted, true)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function permissions(User $user): array
    {
        return $this->load($user)['permissions'];
    }

    /** @return list<string> */
    public function roleNames(User $user): array
    {
        return $this->load($user)['roles'];
    }

    /** @return array{roles: list<string>, permissions: list<string>} */
    private function load(User $user): array
    {
        if (isset($this->cache[$user->id])) {
            return $this->cache[$user->id];
        }

        $roles = $this->roles->rolesForUser($user->id);
        $permissions = $user->isAdmin() ? self::ALL : [];

        foreach ($roles as $role) {
            if (!empty($role['administrator'])) {
                $permissions = self::ALL;
                break;
            }
            foreach (self::ALL as $permission) {
                if (!empty($role[$permission])) {
                    $permissions[] = $permission;
                }
            }
        }

        return $this->cache[$user->id] = [
            'roles' => array_column($roles, 'name'),
            'permissions' => array_values(array_unique($permissions)),
        ];
    }
}
