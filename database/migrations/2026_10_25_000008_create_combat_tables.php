<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: attacklogs, challengebots, challengesbeaten.
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        // outcome replaces the legacy `stole` codes (-1 hospitalized, -2 left, >0 mugged amount).
        $db->query('CREATE TABLE `attack_logs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            attacker_id INT UNSIGNED NOT NULL,
            defender_id INT UNSIGNED NOT NULL,
            result ENUM(\'won\', \'lost\') NOT NULL,
            outcome ENUM(\'mugged\', \'hospitalized\', \'left\', \'lost\') NOT NULL,
            money_stolen BIGINT NOT NULL DEFAULT 0,
            log TEXT NULL,
            created_at DATETIME NOT NULL,
            KEY attack_logs_attacker_index (attacker_id, created_at),
            KEY attack_logs_defender_index (defender_id, created_at)
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `challenge_bots` (
            user_id INT UNSIGNED PRIMARY KEY,
            reward_money BIGINT NOT NULL DEFAULT 0,
            CONSTRAINT challenge_bots_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `challenge_bot_wins` (
            user_id INT UNSIGNED NOT NULL,
            bot_user_id INT UNSIGNED NOT NULL,
            won_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, bot_user_id),
            KEY challenge_bot_wins_bot_index (bot_user_id),
            CONSTRAINT challenge_bot_wins_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT challenge_bot_wins_bot_fk FOREIGN KEY (bot_user_id) REFERENCES challenge_bots (user_id) ON DELETE CASCADE
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        foreach (['challenge_bot_wins', 'challenge_bots', 'attack_logs'] as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
