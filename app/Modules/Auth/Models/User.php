<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Support\Money;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $role,
        public readonly int $level,
        public readonly Money $money,
        public readonly ?string $lastLoginAt = null,
        public readonly bool $forceLogout = false,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            username: (string) $row['username'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            role: (string) $row['role'],
            level: (int) $row['level'],
            money: Money::of((int) $row['money']),
            lastLoginAt: $row['last_login_at'] ?? null,
            forceLogout: !empty($row['force_logout']),
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
