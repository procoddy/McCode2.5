<?php

declare(strict_types=1);

namespace App\Modules\Forums\Repositories;

use App\Core\Database;
use App\Support\Pagination;

/**
 * forums, forum_topics, forum_posts. Counts and "last post" are queried
 * rather than cached on the forum row (legacy recache_forum()).
 */
class ForumRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** Forums visible to someone in $gangId who may or may not see staff forums. */
    public function visibleForums(?int $gangId, bool $staff): array
    {
        return $this->db->fetchAll(
            "SELECT f.*,
                    (SELECT COUNT(*) FROM forum_topics t WHERE t.forum_id = f.id) AS topic_count,
                    (SELECT COUNT(*) FROM forum_posts p JOIN forum_topics t ON t.id = p.topic_id WHERE t.forum_id = f.id) AS post_count,
                    (SELECT MAX(t.last_post_at) FROM forum_topics t WHERE t.forum_id = f.id) AS last_post_at
             FROM forums f
             WHERE f.access = 'public' OR (f.access = 'staff' AND ?) OR (f.access = 'gang' AND f.gang_id = ?)
             ORDER BY FIELD(f.access, 'public', 'gang', 'staff'), f.sort_order, f.name",
            [$staff ? 1 : 0, $gangId ?? 0],
        );
    }

    public function forum(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM forums WHERE id = ?', [$id]);
    }

    public function topics(int $forumId, int $page, int $perPage = 20): Pagination
    {
        $pagination = new Pagination((int) $this->db->fetchColumn('SELECT COUNT(*) FROM forum_topics WHERE forum_id = ?', [$forumId]), $perPage, $page);

        return $pagination->withItems($this->db->fetchAll(
            "SELECT t.*, u.username AS author,
                    (SELECT COUNT(*) FROM forum_posts p WHERE p.topic_id = t.id) AS post_count,
                    (SELECT lu.username FROM forum_posts lp LEFT JOIN users lu ON lu.id = lp.user_id
                     WHERE lp.topic_id = t.id ORDER BY lp.created_at DESC, lp.id DESC LIMIT 1) AS last_poster
             FROM forum_topics t LEFT JOIN users u ON u.id = t.user_id
             WHERE t.forum_id = ?
             ORDER BY t.pinned DESC, t.last_post_at DESC, t.id DESC
             LIMIT {$pagination->perPage} OFFSET {$pagination->offset()}",
            [$forumId],
        ));
    }

    public function topic(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT t.*, f.name AS forum_name, f.access, f.gang_id FROM forum_topics t JOIN forums f ON f.id = t.forum_id WHERE t.id = ?',
            [$id],
        );
    }

    public function posts(int $topicId, int $page, int $perPage = 20): Pagination
    {
        $pagination = new Pagination((int) $this->db->fetchColumn('SELECT COUNT(*) FROM forum_posts WHERE topic_id = ?', [$topicId]), $perPage, $page);

        return $pagination->withItems($this->db->fetchAll(
            "SELECT p.*, u.username AS author, u.level, u.forum_avatar_url, u.forum_signature, e.username AS editor,
                    (SELECT COUNT(*) FROM forum_posts c WHERE c.user_id = p.user_id) AS author_posts
             FROM forum_posts p
             LEFT JOIN users u ON u.id = p.user_id
             LEFT JOIN users e ON e.id = p.edited_by_id
             WHERE p.topic_id = ?
             ORDER BY p.created_at, p.id
             LIMIT {$pagination->perPage} OFFSET {$pagination->offset()}",
            [$topicId],
        ));
    }

    public function post(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT p.*, t.forum_id, t.locked, t.title AS topic_title FROM forum_posts p JOIN forum_topics t ON t.id = p.topic_id WHERE p.id = ?',
            [$id],
        );
    }

    public function createTopic(int $forumId, int $userId, string $title, string $description, string $body, string $now): int
    {
        return $this->db->transaction(function () use ($forumId, $userId, $title, $description, $body, $now): int {
            $topicId = $this->db->insert('forum_topics', [
                'forum_id' => $forumId,
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'created_at' => $now,
                'last_post_at' => $now,
            ]);
            $this->db->insert('forum_posts', ['topic_id' => $topicId, 'user_id' => $userId, 'subject' => $title, 'body' => $body, 'created_at' => $now]);

            return $topicId;
        });
    }

    public function reply(int $topicId, int $userId, string $body, string $now): int
    {
        return $this->db->transaction(function () use ($topicId, $userId, $body, $now): int {
            $id = $this->db->insert('forum_posts', ['topic_id' => $topicId, 'user_id' => $userId, 'body' => $body, 'created_at' => $now]);
            $this->db->update('forum_topics', ['last_post_at' => $now], ['id' => $topicId]);

            return $id;
        });
    }

    public function editPost(int $id, string $body, int $editorId, string $now): void
    {
        $this->db->query(
            'UPDATE forum_posts SET body = ?, edited_by_id = ?, edited_at = ?, edit_count = edit_count + 1 WHERE id = ?',
            [$body, $editorId, $now, $id],
        );
    }

    /** Deleting a topic's last remaining post deletes the topic. Returns true if the topic went too. */
    public function deletePost(int $id): bool
    {
        return $this->db->transaction(function () use ($id): bool {
            $topicId = (int) $this->db->fetchColumn('SELECT topic_id FROM forum_posts WHERE id = ?', [$id]);
            $this->db->delete('forum_posts', ['id' => $id]);

            if ((int) $this->db->fetchColumn('SELECT COUNT(*) FROM forum_posts WHERE topic_id = ?', [$topicId]) === 0) {
                $this->db->delete('forum_topics', ['id' => $topicId]);

                return true;
            }

            $this->db->query(
                'UPDATE forum_topics SET last_post_at = (SELECT MAX(created_at) FROM forum_posts WHERE topic_id = ?) WHERE id = ?',
                [$topicId, $topicId],
            );

            return false;
        });
    }

    public function updateTopic(int $id, array $data): void
    {
        $this->db->update('forum_topics', $data, ['id' => $id]);
    }

    public function deleteTopic(int $id): void
    {
        $this->db->delete('forum_topics', ['id' => $id]);
    }

    /** Page of the topic that contains post $postId. */
    public function pageOf(int $topicId, int $postId, int $perPage = 20): int
    {
        $before = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM forum_posts WHERE topic_id = ? AND id < ?', [$topicId, $postId]);

        return intdiv($before, $perPage) + 1;
    }
}
