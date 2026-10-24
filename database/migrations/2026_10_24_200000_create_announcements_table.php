<?php

declare(strict_types=1);

use App\Core\Database;

return new class {
    public function up(Database $db): void
    {
        $db->query('CREATE TABLE `announcements` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id INT UNSIGNED NULL,
            body TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            KEY announcements_created_at_index (created_at),
            CONSTRAINT announcements_author_fk FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Replaces the legacy `new_announcements` counter: anything newer than this is unread.
        $db->query('ALTER TABLE `users` ADD COLUMN `announcements_read_at` DATETIME NULL');
    }

    public function down(Database $db): void
    {
        $db->query('ALTER TABLE `users` DROP COLUMN `announcements_read_at`');
        $db->query('DROP TABLE IF EXISTS `announcements`');
    }
};
