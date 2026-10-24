<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: forum_forums, forum_topics, forum_posts. Post/topic counts and
 * "last post" details are queried instead of stored, and poster names come
 * from a join instead of being copied into every row.
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE `forums` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description VARCHAR(255) NOT NULL DEFAULT \'\',
            access ENUM(\'public\', \'gang\', \'staff\') NOT NULL DEFAULT \'public\',
            gang_id INT UNSIGNED NULL,
            sort_order INT NOT NULL DEFAULT 0,
            CONSTRAINT forums_gang_fk FOREIGN KEY (gang_id) REFERENCES gangs (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `forum_topics` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            forum_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL,
            title VARCHAR(150) NOT NULL,
            description VARCHAR(255) NOT NULL DEFAULT \'\',
            pinned TINYINT(1) NOT NULL DEFAULT 0,
            locked TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            last_post_at DATETIME NOT NULL,
            KEY forum_topics_listing_index (forum_id, pinned, last_post_at),
            CONSTRAINT forum_topics_forum_fk FOREIGN KEY (forum_id) REFERENCES forums (id) ON DELETE CASCADE,
            CONSTRAINT forum_topics_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `forum_posts` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            topic_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL,
            subject VARCHAR(255) NOT NULL DEFAULT \'\',
            body TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            edited_by_id INT UNSIGNED NULL,
            edited_at DATETIME NULL,
            edit_count INT NOT NULL DEFAULT 0,
            KEY forum_posts_topic_index (topic_id, created_at),
            KEY forum_posts_user_index (user_id),
            CONSTRAINT forum_posts_topic_fk FOREIGN KEY (topic_id) REFERENCES forum_topics (id) ON DELETE CASCADE,
            CONSTRAINT forum_posts_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
            CONSTRAINT forum_posts_editor_fk FOREIGN KEY (edited_by_id) REFERENCES users (id) ON DELETE SET NULL
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        foreach (['forum_posts', 'forum_topics', 'forums'] as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
