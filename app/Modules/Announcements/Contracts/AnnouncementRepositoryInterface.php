<?php

declare(strict_types=1);

namespace App\Modules\Announcements\Contracts;

use App\Support\Pagination;

interface AnnouncementRepositoryInterface
{
    /** Newest first; items are Announcement models. */
    public function paginate(int $page, int $perPage): Pagination;

    public function create(?int $authorId, string $body, string $createdAt): int;

    public function lastReadAt(int $userId): ?string;

    public function markRead(int $userId, string $readAt): void;

    public function countUnread(int $userId): int;
}
