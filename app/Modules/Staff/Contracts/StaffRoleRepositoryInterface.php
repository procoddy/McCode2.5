<?php

declare(strict_types=1);

namespace App\Modules\Staff\Contracts;

interface StaffRoleRepositoryInterface
{
    /** @return list<array<string, mixed>> Full staff_roles rows held by the user. */
    public function rolesForUser(int $userId): array;

    /** @return list<array<string, mixed>> All roles, ordered by id. */
    public function all(): array;

    public function find(int $id): ?array;

    public function nameTaken(string $name, ?int $exceptId = null): bool;

    /** @param array<string, bool> $permissions */
    public function save(?int $id, string $name, array $permissions): int;

    public function delete(int $id): void;

    public function grant(int $userId, int $roleId): bool;

    public function revoke(int $userId, int $roleId): bool;

    /** @return list<array{id: int, username: string, roles: string}> */
    public function members(): array;
}
