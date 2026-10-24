<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: polls (choice1..10 / voted1..10 and users.voted become a JSON list
 * plus poll_votes), papercontent (newspaper), votes (vote_site_claims).
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE `polls` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            question VARCHAR(255) NOT NULL,
            choices JSON NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 0,
            hidden TINYINT(1) NOT NULL DEFAULT 0,
            winning_choice INT NULL,
            created_at DATETIME NOT NULL,
            closed_at DATETIME NULL
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `poll_votes` (
            poll_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            choice INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (poll_id, user_id),
            CONSTRAINT poll_votes_poll_fk FOREIGN KEY (poll_id) REFERENCES polls (id) ON DELETE CASCADE,
            CONSTRAINT poll_votes_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `newspaper` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            body TEXT NOT NULL,
            updated_by_id INT UNSIGNED NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT newspaper_updated_by_fk FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->insert('newspaper', [
            'body' => 'Here you can put game news, or perhaps an update log.',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $db->query('CREATE TABLE `vote_site_claims` (
            user_id INT UNSIGNED NOT NULL,
            site VARCHAR(255) NOT NULL,
            claimed_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, site),
            CONSTRAINT vote_site_claims_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        foreach (['vote_site_claims', 'newspaper', 'poll_votes', 'polls'] as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
