<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: staff_roles, users_roles, stafflog, staffnotelogs, fedjail,
 * jaillogs + unjaillogs (merged into federal_jail_logs), cron_times +
 * logs_cron_runtimes + logs_cron_fails (merged into cron_runs).
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    private const PERMISSIONS = [
        'administrator', 'credit_all_users', 'credit_item', 'credit_user', 'edit_newspaper',
        'manage_challenge_bots', 'manage_cities', 'manage_courses', 'manage_crimes', 'manage_donator_packs',
        'manage_forums', 'manage_gangs', 'manage_houses', 'manage_items', 'manage_jobs', 'manage_player_reports',
        'manage_polls', 'manage_punishments', 'manage_roles', 'manage_shops', 'manage_staff', 'manage_users',
        'mass_mail', 'use_staff_forums', 'view_logs', 'view_user_inventory',
    ];

    public function up(Database $db): void
    {
        $permissionColumns = implode(",\n", array_map(
            fn (string $permission) => "            `{$permission}` TINYINT(1) NOT NULL DEFAULT 0",
            self::PERMISSIONS,
        ));

        $db->query("CREATE TABLE `staff_roles` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
{$permissionColumns}
        ) " . self::OPTIONS);

        // Roles and permissions from the legacy backup.
        $roles = [
            'Administrator' => ['administrator'],
            'Secretary' => ['credit_item', 'credit_user', 'manage_gangs', 'manage_player_reports', 'manage_punishments',
                'use_staff_forums', 'view_logs', 'view_user_inventory'],
            'Assistant' => ['manage_punishments', 'use_staff_forums', 'view_logs'],
            'Helper' => ['manage_polls'],
        ];
        foreach ($roles as $name => $granted) {
            $db->insert('staff_roles', ['name' => $name, ...array_fill_keys($granted, 1)]);
        }

        $db->query('CREATE TABLE `user_staff_roles` (
            user_id INT UNSIGNED NOT NULL,
            staff_role_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (user_id, staff_role_id),
            CONSTRAINT user_staff_roles_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT user_staff_roles_role_fk FOREIGN KEY (staff_role_id) REFERENCES staff_roles (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `staff_logs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            action VARCHAR(255) NOT NULL,
            ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL,
            KEY staff_logs_created_index (created_at)
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `staff_note_logs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            changed_by_id INT UNSIGNED NULL,
            user_id INT UNSIGNED NOT NULL,
            old_notes TEXT NULL,
            new_notes TEXT NULL,
            created_at DATETIME NOT NULL,
            KEY staff_note_logs_user_index (user_id, created_at)
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `federal_jail` (
            user_id INT UNSIGNED PRIMARY KEY,
            jailed_until DATETIME NOT NULL,
            jailed_by_id INT UNSIGNED NULL,
            reason TEXT NOT NULL,
            CONSTRAINT federal_jail_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT federal_jail_staff_fk FOREIGN KEY (jailed_by_id) REFERENCES users (id) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `federal_jail_logs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action ENUM(\'jail\', \'release\') NOT NULL,
            staff_id INT UNSIGNED NULL,
            user_id INT UNSIGNED NOT NULL,
            days INT NULL,
            reason TEXT NULL,
            created_at DATETIME NOT NULL,
            KEY federal_jail_logs_user_index (user_id, created_at)
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `cron_runs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(32) NOT NULL,
            started_at DATETIME NOT NULL,
            finished_at DATETIME NULL,
            updated_count INT NOT NULL DEFAULT 0,
            error TEXT NULL,
            KEY cron_runs_name_index (name, started_at)
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        foreach (['cron_runs', 'federal_jail_logs', 'federal_jail', 'staff_note_logs', 'staff_logs', 'user_staff_roles', 'staff_roles'] as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
