<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Models\User;
use App\Support\Money;

final class InMemoryAuthRepository implements AuthRepositoryInterface
{
    /** @var array<int, User> */
    public array $users = [];

    public array $logins = [];

    public function add(string $username, string $password, string $role = 'player'): User
    {
        $user = new User(
            id: count($this->users) + 1,
            username: $username,
            email: "{$username}@example.test",
            passwordHash: password_hash($password, PASSWORD_DEFAULT),
            role: $role,
            level: 1,
            money: Money::of(100),
        );

        return $this->users[$user->id] = $user;
    }

    public function findById(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByLogin(string $login): ?User
    {
        foreach ($this->users as $user) {
            if ($user->username === $login || $user->email === $login) {
                return $user;
            }
        }

        return null;
    }

    public function usernameExists(string $username): bool
    {
        return $this->findByLogin($username) !== null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByLogin($email) !== null;
    }

    public function create(array $attributes): User
    {
        $user = new User(
            id: count($this->users) + 1,
            username: $attributes['username'],
            email: $attributes['email'],
            passwordHash: $attributes['password_hash'],
            role: 'player',
            level: 1,
            money: Money::of(100),
        );

        return $this->users[$user->id] = $user;
    }

    public function updatePasswordHash(int $userId, string $hash): void
    {
    }

    public function clearForceLogout(int $userId): void
    {
    }

    public function recordLogin(int $userId, string $ip): void
    {
        $this->logins[] = [$userId, $ip];
    }
}
