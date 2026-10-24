<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: orgcrimes, gangs, applications, gangevents, gangwars, surrenders, oclogs.
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE `organised_crimes` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            members_required INT NOT NULL DEFAULT 1,
            start_text TEXT NULL,
            success_text TEXT NULL,
            failure_text TEXT NULL,
            min_money BIGINT NOT NULL DEFAULT 0,
            max_money BIGINT NOT NULL DEFAULT 0
        ) ' . self::OPTIONS);

        // crime_ready_at replaces the legacy gangCHOURS countdown.
        $db->query('CREATE TABLE `gangs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            prefix VARCHAR(12) NOT NULL DEFAULT \'\',
            suffix VARCHAR(12) NOT NULL DEFAULT \'\',
            money BIGINT NOT NULL DEFAULT 0,
            crystals INT NOT NULL DEFAULT 0,
            respect INT NOT NULL DEFAULT 100,
            president_id INT UNSIGNED NULL,
            vice_president_id INT UNSIGNED NULL,
            capacity INT NOT NULL DEFAULT 5,
            organised_crime_id INT UNSIGNED NULL,
            crime_ready_at DATETIME NULL,
            announcement TEXT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY gangs_name_unique (name),
            CONSTRAINT gangs_president_fk FOREIGN KEY (president_id) REFERENCES users (id) ON DELETE SET NULL,
            CONSTRAINT gangs_vice_president_fk FOREIGN KEY (vice_president_id) REFERENCES users (id) ON DELETE SET NULL,
            CONSTRAINT gangs_organised_crime_fk FOREIGN KEY (organised_crime_id) REFERENCES organised_crimes (id) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `gang_applications` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            gang_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            body TEXT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY gang_applications_unique (gang_id, user_id),
            CONSTRAINT gang_applications_gang_fk FOREIGN KEY (gang_id) REFERENCES gangs (id) ON DELETE CASCADE,
            CONSTRAINT gang_applications_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `gang_events` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            gang_id INT UNSIGNED NOT NULL,
            body TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            KEY gang_events_gang_index (gang_id, created_at),
            CONSTRAINT gang_events_gang_fk FOREIGN KEY (gang_id) REFERENCES gangs (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `gang_wars` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            declarer_gang_id INT UNSIGNED NOT NULL,
            declared_gang_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY gang_wars_pair_unique (declarer_gang_id, declared_gang_id),
            CONSTRAINT gang_wars_declarer_fk FOREIGN KEY (declarer_gang_id) REFERENCES gangs (id) ON DELETE CASCADE,
            CONSTRAINT gang_wars_declared_fk FOREIGN KEY (declared_gang_id) REFERENCES gangs (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `gang_war_surrenders` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            war_id INT UNSIGNED NOT NULL,
            from_gang_id INT UNSIGNED NOT NULL,
            to_gang_id INT UNSIGNED NOT NULL,
            message VARCHAR(255) NOT NULL DEFAULT \'\',
            created_at DATETIME NOT NULL,
            CONSTRAINT gang_war_surrenders_war_fk FOREIGN KEY (war_id) REFERENCES gang_wars (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `organised_crime_logs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organised_crime_id INT UNSIGNED NULL,
            gang_id INT UNSIGNED NULL,
            crime_name VARCHAR(255) NOT NULL DEFAULT \'\',
            result ENUM(\'success\', \'failure\') NOT NULL,
            money BIGINT NOT NULL DEFAULT 0,
            log TEXT NULL,
            created_at DATETIME NOT NULL,
            KEY organised_crime_logs_gang_index (gang_id, created_at)
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        foreach (['organised_crime_logs', 'gang_war_surrenders', 'gang_wars', 'gang_events', 'gang_applications', 'gangs', 'organised_crimes'] as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
