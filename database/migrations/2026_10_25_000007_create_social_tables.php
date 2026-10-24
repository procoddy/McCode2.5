<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: events, mail, friendslist + blacklist + contactlist (merged into
 * user_lists), preports, referals.
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE `events` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            body TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            read_at DATETIME NULL,
            KEY events_user_index (user_id, created_at),
            CONSTRAINT events_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        // sender_id NULL = system message.
        $db->query('CREATE TABLE `mail` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sender_id INT UNSIGNED NULL,
            recipient_id INT UNSIGNED NOT NULL,
            subject VARCHAR(255) NOT NULL DEFAULT \'\',
            body TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            read_at DATETIME NULL,
            KEY mail_recipient_index (recipient_id, created_at),
            KEY mail_sender_index (sender_id, created_at),
            CONSTRAINT mail_sender_fk FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE SET NULL,
            CONSTRAINT mail_recipient_fk FOREIGN KEY (recipient_id) REFERENCES users (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `user_lists` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            owner_id INT UNSIGNED NOT NULL,
            target_id INT UNSIGNED NOT NULL,
            list ENUM(\'friend\', \'enemy\', \'contact\') NOT NULL,
            comment VARCHAR(255) NOT NULL DEFAULT \'\',
            created_at DATETIME NOT NULL,
            UNIQUE KEY user_lists_unique (owner_id, target_id, list),
            KEY user_lists_target_index (target_id, list),
            CONSTRAINT user_lists_owner_fk FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT user_lists_target_fk FOREIGN KEY (target_id) REFERENCES users (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `player_reports` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT UNSIGNED NULL,
            reported_id INT UNSIGNED NULL,
            body TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT player_reports_reporter_fk FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE SET NULL,
            CONSTRAINT player_reports_reported_fk FOREIGN KEY (reported_id) REFERENCES users (id) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `referrals` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            referrer_id INT UNSIGNED NOT NULL,
            referred_id INT UNSIGNED NOT NULL,
            referrer_ip VARCHAR(45) NULL,
            referred_ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY referrals_referred_unique (referred_id),
            CONSTRAINT referrals_referrer_fk FOREIGN KEY (referrer_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT referrals_referred_fk FOREIGN KEY (referred_id) REFERENCES users (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        foreach (['referrals', 'player_reports', 'user_lists', 'mail', 'events'] as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
