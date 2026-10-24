<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * The remaining legacy `users` columns.
 *
 * Not carried over, because they are derived or replaced:
 * - new_events, new_mail, new_announcements: counted from read_at columns
 * - friend_count, enemy_count, posts: counted from user_lists / forum_posts
 * - daysold, signedup: created_at; lastip_login, last_login: last_login_ip / last_login_at
 * - voted: poll_votes; fedjail: federal_jail; user_level: role (+ user_staff_roles)
 * - login_name, userpass, pass_salt: username/email + password_hash
 * - cdays, donatordays, daysingang, mailban, forumban: *_ends_at / *_until / *_at dates
 */
return new class {
    private const FOREIGN_KEYS = [
        'users_city_fk' => ['city_id', 'cities'],
        'users_house_fk' => ['house_id', 'houses'],
        'users_gang_fk' => ['gang_id', 'gangs'],
        'users_job_fk' => ['job_id', 'jobs'],
        'users_job_rank_fk' => ['job_rank_id', 'job_ranks'],
        'users_course_fk' => ['course_id', 'courses'],
        'users_equipped_primary_fk' => ['equipped_primary_id', 'items'],
        'users_equipped_secondary_fk' => ['equipped_secondary_id', 'items'],
        'users_equipped_armor_fk' => ['equipped_armor_id', 'items'],
        'users_attacking_fk' => ['attacking_user_id', 'users'],
    ];

    public function up(Database $db): void
    {
        $db->query('ALTER TABLE `users`
            ADD COLUMN `gender` ENUM(\'Male\', \'Female\') NOT NULL DEFAULT \'Male\',
            ADD COLUMN `account_type` ENUM(\'Demo\', \'Active\', \'Staff\', \'NPC\') NOT NULL DEFAULT \'Active\',
            ADD COLUMN `city_id` INT UNSIGNED NULL DEFAULT 1,
            ADD COLUMN `house_id` INT UNSIGNED NULL DEFAULT 1,
            ADD COLUMN `gang_id` INT UNSIGNED NULL,
            ADD COLUMN `gang_joined_at` DATETIME NULL,
            ADD COLUMN `job_id` INT UNSIGNED NULL,
            ADD COLUMN `job_rank_id` INT UNSIGNED NULL,
            ADD COLUMN `course_id` INT UNSIGNED NULL,
            ADD COLUMN `course_ends_at` DATETIME NULL,
            ADD COLUMN `donator_until` DATETIME NULL,
            ADD COLUMN `equipped_primary_id` INT UNSIGNED NULL,
            ADD COLUMN `equipped_secondary_id` INT UNSIGNED NULL,
            ADD COLUMN `equipped_armor_id` INT UNSIGNED NULL,
            ADD COLUMN `attacking_user_id` INT UNSIGNED NULL,
            ADD COLUMN `avatar_url` VARCHAR(255) NULL,
            ADD COLUMN `duties` VARCHAR(255) NOT NULL DEFAULT \'N/A\',
            ADD COLUMN `staff_notes` TEXT NULL,
            ADD COLUMN `notepad` TEXT NULL,
            ADD COLUMN `mail_banned_until` DATETIME NULL,
            ADD COLUMN `mail_ban_reason` VARCHAR(255) NULL,
            ADD COLUMN `forum_banned_until` DATETIME NULL,
            ADD COLUMN `forum_ban_reason` VARCHAR(255) NULL,
            ADD COLUMN `forum_avatar_url` VARCHAR(255) NULL,
            ADD COLUMN `forum_signature` VARCHAR(250) NULL,
            ADD COLUMN `boxes_opened` INT NOT NULL DEFAULT 0,
            ADD COLUMN `verified` TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN `force_logout` TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN `signup_ip` VARCHAR(45) NULL,
            ADD COLUMN `last_seen_at` DATETIME NULL,
            ADD KEY users_last_seen_index (last_seen_at)');

        foreach (self::FOREIGN_KEYS as $name => [$column, $table]) {
            $db->query("ALTER TABLE `users` ADD CONSTRAINT {$name} FOREIGN KEY ({$column}) REFERENCES {$table} (id) ON DELETE SET NULL");
        }
    }

    public function down(Database $db): void
    {
        foreach (array_keys(self::FOREIGN_KEYS) as $name) {
            $db->query("ALTER TABLE `users` DROP FOREIGN KEY {$name}");
        }

        $db->query('ALTER TABLE `users` DROP KEY users_last_seen_index');

        $columns = ['gender', 'account_type', 'city_id', 'house_id', 'gang_id', 'gang_joined_at', 'job_id', 'job_rank_id',
            'course_id', 'course_ends_at', 'donator_until', 'equipped_primary_id', 'equipped_secondary_id', 'equipped_armor_id',
            'attacking_user_id', 'avatar_url', 'duties', 'staff_notes', 'notepad', 'mail_banned_until', 'mail_ban_reason',
            'forum_banned_until', 'forum_ban_reason', 'forum_avatar_url', 'forum_signature', 'boxes_opened', 'verified',
            'force_logout', 'signup_ip', 'last_seen_at'];

        $db->query('ALTER TABLE `users` ' . implode(', ', array_map(fn ($c) => "DROP COLUMN `{$c}`", $columns)));
    }
};
