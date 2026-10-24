<?php

declare(strict_types=1);

namespace App\Modules\Staff\Repositories;

use App\Core\Database;
use App\Modules\Staff\Contracts\StaffRoleRepositoryInterface;
use App\Modules\Staff\Services\StaffPermissions;

final class StaffRoleRepository implements StaffRoleRepositoryInterface
{
    public function __construct(private readonly Database $db)
    {
    }

    public function rolesForUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT r.* FROM staff_roles r JOIN user_staff_roles ur ON ur.staff_role_id = r.id WHERE ur.user_id = ? ORDER BY r.id',
            [$userId],
        );
    }

    public function all(): array
    {
        return $this->db->fetchAll('SELECT * FROM staff_roles ORDER BY id');
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM staff_roles WHERE id = ?', [$id]);
    }

    public function nameTaken(string $name, ?int $exceptId = null): bool
    {
        return $this->db->fetchColumn(
            'SELECT 1 FROM staff_roles WHERE LOWER(name) = LOWER(?) AND id <> ?',
            [$name, $exceptId ?? 0],
        ) !== null;
    }

    public function save(?int $id, string $name, array $permissions): int
    {
        $data = ['name' => $name];
        foreach (StaffPermissions::ALL as $permission) {
            $data[$permission] = empty($permissions[$permission]) ? 0 : 1;
        }

        if ($id === null) {
            return $this->db->insert('staff_roles', $data);
        }

        $this->db->update('staff_roles', $data, ['id' => $id]);

        return $id;
    }

    public function delete(int $id): void
    {
        $this->db->delete('staff_roles', ['id' => $id]);
    }

    public function grant(int $userId, int $roleId): bool
    {
        return $this->db->query(
            'INSERT IGNORE INTO user_staff_roles (user_id, staff_role_id) VALUES (?, ?)',
            [$userId, $roleId],
        )->rowCount() === 1;
    }

    public function revoke(int $userId, int $roleId): bool
    {
        return $this->db->delete('user_staff_roles', ['user_id' => $userId, 'staff_role_id' => $roleId]) > 0;
    }

    public function members(): array
    {
        return array_map(
            fn (array $row) => ['id' => (int) $row['id'], 'username' => $row['username'], 'roles' => (string) $row['roles']],
            $this->db->fetchAll(
                'SELECT u.id, u.username, GROUP_CONCAT(r.name ORDER BY r.id SEPARATOR \', \') AS roles
                 FROM users u
                 JOIN user_staff_roles ur ON ur.user_id = u.id
                 JOIN staff_roles r ON r.id = ur.staff_role_id
                 GROUP BY u.id, u.username
                 ORDER BY u.username',
            ),
        );
    }
}
