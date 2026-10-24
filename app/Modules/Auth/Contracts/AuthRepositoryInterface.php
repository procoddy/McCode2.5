<?php

declare(strict_types=1);

namespace App\Modules\Auth\Contracts;

use App\Modules\Auth\Models\User;

interface AuthRepositoryInterface
{
    public function findById(int $id): ?User;

    /** Look up by username or email address. */
    public function findByLogin(string $login): ?User;

    public function usernameExists(string $username): bool;

    public function emailExists(string $email): bool;

    /**
     * @param array{username: string, email: string, password_hash: string} $attributes
     */
    public function create(array $attributes): User;

    public function updatePasswordHash(int $userId, string $hash): void;

    public function recordLogin(int $userId, string $ip): void;

    /** Clears the staff "force logout" flag once it has been applied. */
    public function clearForceLogout(int $userId): void;
}
