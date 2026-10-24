<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Exceptions\ValidationException;
use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Models\User;
use PDOException;

final class AuthRepository implements AuthRepositoryInterface
{
    public function __construct(private readonly Database $db, private readonly ClockInterface $clock)
    {
    }

    public function findById(int $id): ?User
    {
        $row = $this->db->fetch('SELECT * FROM users WHERE id = ?', [$id]);

        return $row === null ? null : User::fromRow($row);
    }

    public function findByLogin(string $login): ?User
    {
        $row = $this->db->fetch('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1', [$login, $login]);

        return $row === null ? null : User::fromRow($row);
    }

    public function usernameExists(string $username): bool
    {
        return $this->db->fetchColumn('SELECT 1 FROM users WHERE username = ? LIMIT 1', [$username]) !== null;
    }

    public function emailExists(string $email): bool
    {
        return $this->db->fetchColumn('SELECT 1 FROM users WHERE email = ? LIMIT 1', [$email]) !== null;
    }

    public function create(array $attributes): User
    {
        try {
            $id = $this->db->insert('users', [
                'username' => $attributes['username'],
                'email' => $attributes['email'],
                'password_hash' => $attributes['password_hash'],
                'signup_ip' => $attributes['signup_ip'] ?? null,
                'created_at' => $this->clock->now()->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            // Unique index caught a sign-up that raced past the exists() checks.
            if ($e->getCode() === '23000') {
                throw ValidationException::withMessages(['username' => 'That username or email is already taken.']);
            }
            throw $e;
        }

        return $this->findById($id);
    }

    public function updatePasswordHash(int $userId, string $hash): void
    {
        $this->db->update('users', ['password_hash' => $hash], ['id' => $userId]);
    }

    public function clearForceLogout(int $userId): void
    {
        $this->db->update('users', ['force_logout' => 0], ['id' => $userId]);
    }

    public function recordLogin(int $userId, string $ip): void
    {
        $this->db->update('users', [
            'last_login_at' => $this->clock->now()->format('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
        ], ['id' => $userId]);
    }
}
