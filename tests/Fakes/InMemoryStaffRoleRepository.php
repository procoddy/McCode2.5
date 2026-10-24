<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Modules\Staff\Contracts\StaffRoleRepositoryInterface;

final class InMemoryStaffRoleRepository implements StaffRoleRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $roles = [];

    /** @var array<int, list<int>> user id => role ids */
    public array $members = [];

    /** @param list<string> $permissions */
    public function addRole(string $name, array $permissions): int
    {
        $id = count($this->roles) + 1;
        $this->roles[$id] = ['id' => $id, 'name' => $name, ...array_fill_keys($permissions, 1)];

        return $id;
    }

    public function rolesForUser(int $userId): array
    {
        return array_values(array_map(fn (int $id) => $this->roles[$id], $this->members[$userId] ?? []));
    }

    public function all(): array
    {
        return array_values($this->roles);
    }

    public function find(int $id): ?array
    {
        return $this->roles[$id] ?? null;
    }

    public function nameTaken(string $name, ?int $exceptId = null): bool
    {
        foreach ($this->roles as $role) {
            if (strcasecmp($role['name'], $name) === 0 && $role['id'] !== $exceptId) {
                return true;
            }
        }

        return false;
    }

    public function save(?int $id, string $name, array $permissions): int
    {
        $id ??= count($this->roles) + 1;
        $this->roles[$id] = ['id' => $id, 'name' => $name, ...array_map(fn ($v) => $v ? 1 : 0, $permissions)];

        return $id;
    }

    public function delete(int $id): void
    {
        unset($this->roles[$id]);
    }

    public function grant(int $userId, int $roleId): bool
    {
        if (in_array($roleId, $this->members[$userId] ?? [], true)) {
            return false;
        }
        $this->members[$userId][] = $roleId;

        return true;
    }

    public function revoke(int $userId, int $roleId): bool
    {
        $before = count($this->members[$userId] ?? []);
        $this->members[$userId] = array_values(array_diff($this->members[$userId] ?? [], [$roleId]));

        return count($this->members[$userId]) < $before;
    }

    public function members(): array
    {
        return [];
    }
}
