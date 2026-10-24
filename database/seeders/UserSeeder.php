<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;
use Database\Factories\UserFactory;

final class UserSeeder
{
    public function run(Database $db): void
    {
        if ($db->fetchColumn('SELECT id FROM users WHERE username = ?', ['admin']) !== null) {
            return;
        }

        // Development only: change this password immediately on any shared server.
        $db->insert('users', UserFactory::make([
            'username' => 'admin',
            'email' => 'admin@example.test',
            'role' => 'admin',
        ]));

        for ($i = 0; $i < 5; $i++) {
            $db->insert('users', UserFactory::make());
        }
    }
}
