<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: itemtypes, items, inventory, shops, shopitems, itemmarket and the six
 * item log tables (itembuylogs, itemselllogs, imarketaddlogs, imbuylogs,
 * imremovelogs, itemxferlogs), merged into item_logs with a type column.
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE `item_types` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        ) ' . self::OPTIONS);

        // effects: JSON list replacing effect1..3 / effect1_on..3_on.
        $db->query('CREATE TABLE `items` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type_id INT UNSIGNED NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            buy_price BIGINT NOT NULL DEFAULT 0,
            sell_price BIGINT NOT NULL DEFAULT 0,
            buyable TINYINT(1) NOT NULL DEFAULT 0,
            effects JSON NULL,
            weapon INT NOT NULL DEFAULT 0,
            armor INT NOT NULL DEFAULT 0,
            CONSTRAINT items_type_fk FOREIGN KEY (type_id) REFERENCES item_types (id) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `inventory` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            item_id INT UNSIGNED NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            UNIQUE KEY inventory_user_item_unique (user_id, item_id),
            CONSTRAINT inventory_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT inventory_item_fk FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `shops` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            city_id INT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            CONSTRAINT shops_city_fk FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `shop_items` (
            shop_id INT UNSIGNED NOT NULL,
            item_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (shop_id, item_id),
            CONSTRAINT shop_items_shop_fk FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
            CONSTRAINT shop_items_item_fk FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `item_market_listings` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            seller_id INT UNSIGNED NOT NULL,
            item_id INT UNSIGNED NOT NULL,
            price BIGINT NOT NULL,
            currency ENUM(\'money\', \'crystals\') NOT NULL DEFAULT \'money\',
            quantity INT NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            CONSTRAINT item_market_seller_fk FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT item_market_item_fk FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        // Logs keep plain ids (no foreign keys) so history survives deletions.
        $db->query('CREATE TABLE `item_logs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM(\'shop_buy\', \'shop_sell\', \'market_add\', \'market_buy\', \'market_remove\', \'transfer\') NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            other_user_id INT UNSIGNED NULL,
            item_id INT UNSIGNED NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price BIGINT NOT NULL DEFAULT 0,
            currency ENUM(\'money\', \'crystals\') NOT NULL DEFAULT \'money\',
            user_ip VARCHAR(45) NULL,
            other_user_ip VARCHAR(45) NULL,
            details TEXT NULL,
            created_at DATETIME NOT NULL,
            KEY item_logs_user_index (user_id, created_at),
            KEY item_logs_type_index (type, created_at)
        ) ' . self::OPTIONS);

        // crimes.success_item_id existed before items did.
        $db->query('ALTER TABLE `crimes`
            ADD CONSTRAINT crimes_item_fk FOREIGN KEY (success_item_id) REFERENCES items (id) ON DELETE SET NULL');
    }

    public function down(Database $db): void
    {
        $db->query('ALTER TABLE `crimes` DROP FOREIGN KEY crimes_item_fk');
        foreach (['item_logs', 'item_market_listings', 'shop_items', 'shops', 'inventory', 'items', 'item_types'] as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
