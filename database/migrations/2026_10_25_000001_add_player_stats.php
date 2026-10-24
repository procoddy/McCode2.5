<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Player state used by banking, crimes and the crystal temple.
 * Legacy differences:
 * - bank_balance / cyber_balance are NULL when there is no account (legacy used -1).
 * - jail / hospital are end times instead of minute counters, so no cron has to count them down.
 */
return new class {
    public function up(Database $db): void
    {
        $db->query('ALTER TABLE `users`
            ADD COLUMN `exp` BIGINT NOT NULL DEFAULT 0,
            ADD COLUMN `crime_exp` INT NOT NULL DEFAULT 0,
            ADD COLUMN `crystals` INT NOT NULL DEFAULT 0,
            ADD COLUMN `energy` INT NOT NULL DEFAULT 12,
            ADD COLUMN `max_energy` INT NOT NULL DEFAULT 12,
            ADD COLUMN `brave` INT NOT NULL DEFAULT 5,
            ADD COLUMN `max_brave` INT NOT NULL DEFAULT 5,
            ADD COLUMN `will` INT NOT NULL DEFAULT 100,
            ADD COLUMN `max_will` INT NOT NULL DEFAULT 100,
            ADD COLUMN `hp` INT NOT NULL DEFAULT 100,
            ADD COLUMN `max_hp` INT NOT NULL DEFAULT 100,
            ADD COLUMN `bank_balance` BIGINT NULL,
            ADD COLUMN `cyber_balance` BIGINT NULL,
            ADD COLUMN `jail_until` DATETIME NULL,
            ADD COLUMN `jail_reason` VARCHAR(255) NULL,
            ADD COLUMN `hospital_until` DATETIME NULL,
            ADD COLUMN `hospital_reason` VARCHAR(255) NULL');

        $db->query('CREATE TABLE `user_stats` (
            user_id INT UNSIGNED PRIMARY KEY,
            strength DOUBLE NOT NULL DEFAULT 10,
            agility DOUBLE NOT NULL DEFAULT 10,
            guard DOUBLE NOT NULL DEFAULT 10,
            labour DOUBLE NOT NULL DEFAULT 10,
            iq DOUBLE NOT NULL DEFAULT 10,
            CONSTRAINT user_stats_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB');

        $db->query('INSERT INTO `user_stats` (user_id) SELECT id FROM `users`');

        $db->query('CREATE TABLE `crime_groups` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $db->query('CREATE TABLE `crimes` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            group_id INT UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            brave_cost INT NOT NULL,
            success_formula VARCHAR(255) NOT NULL,
            success_money INT NOT NULL DEFAULT 0,
            success_crystals INT NOT NULL DEFAULT 0,
            success_item_id INT UNSIGNED NULL,
            crime_exp INT NOT NULL DEFAULT 0,
            jail_minutes INT NOT NULL DEFAULT 0,
            jail_reason VARCHAR(255) NOT NULL DEFAULT \'\',
            initial_text TEXT NOT NULL,
            success_text TEXT NOT NULL,
            failure_text TEXT NOT NULL,
            jail_text TEXT NOT NULL,
            CONSTRAINT crimes_group_fk FOREIGN KEY (group_id) REFERENCES crime_groups (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Database $db): void
    {
        $db->query('DROP TABLE IF EXISTS `crimes`');
        $db->query('DROP TABLE IF EXISTS `crime_groups`');
        $db->query('DROP TABLE IF EXISTS `user_stats`');
        $db->query('ALTER TABLE `users`
            DROP COLUMN `exp`, DROP COLUMN `crime_exp`, DROP COLUMN `crystals`,
            DROP COLUMN `energy`, DROP COLUMN `max_energy`, DROP COLUMN `brave`, DROP COLUMN `max_brave`,
            DROP COLUMN `will`, DROP COLUMN `max_will`, DROP COLUMN `hp`, DROP COLUMN `max_hp`,
            DROP COLUMN `bank_balance`, DROP COLUMN `cyber_balance`,
            DROP COLUMN `jail_until`, DROP COLUMN `jail_reason`,
            DROP COLUMN `hospital_until`, DROP COLUMN `hospital_reason`');
    }
};
