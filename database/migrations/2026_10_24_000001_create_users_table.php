<?php

declare(strict_types=1);

use App\Core\Database;

return new class {
    public function up(Database $db): void
    {
        if ($db->driver() === 'sqlite') {
            $db->query('CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(32) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(16) NOT NULL DEFAULT \'player\',
                level INTEGER NOT NULL DEFAULT 1,
                money INTEGER NOT NULL DEFAULT 100,
                last_login_at DATETIME NULL,
                last_login_ip VARCHAR(45) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )');

            return;
        }

        $db->query('CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(32) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(16) NOT NULL DEFAULT \'player\',
            level INT UNSIGNED NOT NULL DEFAULT 1,
            money BIGINT NOT NULL DEFAULT 100,
            last_login_at DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY users_username_unique (username),
            UNIQUE KEY users_email_unique (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Database $db): void
    {
        $db->query('DROP TABLE IF EXISTS users');
    }
};
