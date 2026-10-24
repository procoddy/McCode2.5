<?php

declare(strict_types=1);

namespace App\Modules\Announcements\Repositories;

use App\Core\Database;
use App\Modules\Announcements\Contracts\AnnouncementRepositoryInterface;
use App\Modules\Announcements\Models\Announcement;
use App\Support\Pagination;

final class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function __construct(private readonly Database $db)
    {
    }

    public function paginate(int $page, int $perPage): Pagination
    {
        $pagination = new Pagination((int) $this->db->fetchColumn('SELECT COUNT(*) FROM announcements'), $perPage, $page);

        $rows = $this->db->fetchAll(
            'SELECT a.id, a.body, a.created_at, u.username AS author_name
             FROM announcements a
             LEFT JOIN users u ON u.id = a.author_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT ' . $pagination->perPage . ' OFFSET ' . $pagination->offset(),
        );

        return $pagination->withItems(array_map(Announcement::fromRow(...), $rows));
    }

    public function create(?int $authorId, string $body, string $createdAt): int
    {
        return $this->db->insert('announcements', [
            'author_id' => $authorId,
            'body' => $body,
            'created_at' => $createdAt,
        ]);
    }

    public function lastReadAt(int $userId): ?string
    {
        return $this->db->fetchColumn('SELECT announcements_read_at FROM users WHERE id = ?', [$userId]);
    }

    public function markRead(int $userId, string $readAt): void
    {
        $this->db->update('users', ['announcements_read_at' => $readAt], ['id' => $userId]);
    }

    public function countUnread(int $userId): int
    {
        return (int) $this->db->fetchColumn(
            'SELECT COUNT(*)
             FROM announcements a
             JOIN users u ON u.id = ?
             WHERE u.announcements_read_at IS NULL OR a.created_at > u.announcements_read_at',
            [$userId],
        );
    }
}
