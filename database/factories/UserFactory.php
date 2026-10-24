<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Support\Str;

final class UserFactory
{
    /** Plain-text password for every generated user. */
    public const PASSWORD = 'password';

    public static function make(array $overrides = []): array
    {
        $username = strtolower(Str::random(8));

        return [
            'username' => $username,
            'email' => "{$username}@example.test",
            'password_hash' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
            'role' => 'player',
            'level' => 1,
            'money' => 100,
            ...$overrides,
        ];
    }
}
