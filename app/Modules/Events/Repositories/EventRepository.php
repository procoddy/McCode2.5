<?php

declare(strict_types=1);

namespace App\Modules\Events\Repositories;

use App\Core\Database;
use App\Modules\Events\Contracts\EventRepositoryInterface;
use App\Support\Pagination;

final class EventRepository implements EventRepositoryInterface
{
    public function __construct(private readonly Database $db)
    {
    }

    public function add(int $userId, string $body, string $createdAt): void
    {
        $this->db->insert('events', ['user_id' => $userId, 'body' => $body, 'created_at' => $createdAt]);
    }

    public function paginateFor(int $userId, int $page, int $perPage): Pagination
    {
        $pagination = new Pagination(
            (int) $this->db->fetchColumn('SELECT COUNT(*) FROM events WHERE user_id = ?', [$userId]),
            $perPage,
            $page,
        );

        return $pagination->withItems($this->db->fetchAll(
            'SELECT id, body, created_at, read_at FROM events WHERE user_id = ?
             ORDER BY created_at DESC, id DESC LIMIT ' . $pagination->perPage . ' OFFSET ' . $pagination->offset(),
            [$userId],
        ));
    }

    public function countUnread(int $userId): int
    {
        return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM events WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }

    public function markAllRead(int $userId, string $readAt): void
    {
        $this->db->query('UPDATE events SET read_at = ? WHERE user_id = ? AND read_at IS NULL', [$readAt, $userId]);
    }

    public function deleteAll(int $userId): void
    {
        $this->db->delete('events', ['user_id' => $userId]);
    }
}
