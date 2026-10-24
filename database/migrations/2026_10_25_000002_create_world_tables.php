<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: cities, houses, settings.
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE `cities` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            min_level INT NOT NULL DEFAULT 1
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `houses` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price BIGINT NOT NULL DEFAULT 0,
            will INT NOT NULL DEFAULT 100
        ) ' . self::OPTIONS);

        // Staff-editable values. Game balance numbers live in config/game.php.
        $db->query('CREATE TABLE `settings` (
            name VARCHAR(64) PRIMARY KEY,
            value TEXT NOT NULL,
            type ENUM(\'string\', \'int\', \'bool\') NOT NULL DEFAULT \'string\'
        ) ' . self::OPTIONS);

        $db->insert('cities', ['id' => 1, 'name' => 'Default City', 'description' => 'A standard city added to start you off', 'min_level' => 1]);
        $db->insert('houses', ['id' => 1, 'name' => 'Default House', 'price' => 0, 'will' => 100]);
        $db->insert('houses', ['id' => 2, 'name' => 'New House', 'price' => 1000, 'will' => 50]);

        foreach ([
            ['validate_period', '15', 'string'],
            ['validate_on', '0', 'bool'],
            ['regcap_on', '0', 'bool'],
            ['sendcrys_on', '1', 'bool'],
            ['sendbank_on', '1', 'bool'],
            ['staff_pad', 'Here you can store notes for all staff to see.', 'string'],
            ['willp_item', '3', 'int'],
        ] as [$name, $value, $type]) {
            $db->insert('settings', ['name' => $name, 'value' => $value, 'type' => $type]);
        }
    }

    public function down(Database $db): void
    {
        $db->query('DROP TABLE IF EXISTS `settings`');
        $db->query('DROP TABLE IF EXISTS `houses`');
        $db->query('DROP TABLE IF EXISTS `cities`');
    }
};
