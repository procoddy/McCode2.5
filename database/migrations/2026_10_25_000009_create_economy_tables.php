<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: cashxferlogs + bankxferlogs + crystalxferlogs (merged into
 * transfer_logs), crystalmarket, dps_accepted + willps_accepted (merged into
 * donation_payments).
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE `transfer_logs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM(\'cash\', \'bank\', \'cyber\', \'crystals\') NOT NULL,
            from_user_id INT UNSIGNED NOT NULL,
            to_user_id INT UNSIGNED NOT NULL,
            amount BIGINT NOT NULL,
            from_ip VARCHAR(45) NULL,
            to_ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL,
            KEY transfer_logs_from_index (from_user_id, created_at),
            KEY transfer_logs_to_index (to_user_id, created_at)
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `crystal_market_listings` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            seller_id INT UNSIGNED NOT NULL,
            quantity INT NOT NULL,
            price_each BIGINT NOT NULL,
            created_at DATETIME NOT NULL,
            KEY crystal_market_price_index (price_each),
            CONSTRAINT crystal_market_seller_fk FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        // transaction_id is unique so one payment can never be credited twice.
        $db->query('CREATE TABLE `donation_payments` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM(\'donator_pack\', \'will_potion\') NOT NULL,
            buyer_id INT UNSIGNED NULL,
            for_user_id INT UNSIGNED NULL,
            pack VARCHAR(255) NOT NULL DEFAULT \'\',
            quantity INT NOT NULL DEFAULT 1,
            transaction_id VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY donation_payments_transaction_unique (transaction_id),
            CONSTRAINT donation_payments_buyer_fk FOREIGN KEY (buyer_id) REFERENCES users (id) ON DELETE SET NULL,
            CONSTRAINT donation_payments_for_fk FOREIGN KEY (for_user_id) REFERENCES users (id) ON DELETE SET NULL
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        foreach (['donation_payments', 'crystal_market_listings', 'transfer_logs'] as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
