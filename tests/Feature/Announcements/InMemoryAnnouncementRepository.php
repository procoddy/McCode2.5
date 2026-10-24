<?php

declare(strict_types=1);

namespace Tests\Feature\Announcements;

use App\Modules\Announcements\Contracts\AnnouncementRepositoryInterface;
use App\Modules\Announcements\Models\Announcement;
use App\Support\Pagination;

final class InMemoryAnnouncementRepository implements AnnouncementRepositoryInterface
{
    /** @var list<Announcement> */
    public array $announcements = [];

    /** @var array<int, string> user id => read at */
    public array $readAt = [];

    public function paginate(int $page, int $perPage): Pagination
    {
        $sorted = $this->announcements;
        usort($sorted, fn (Announcement $a, Announcement $b) => [$b->createdAt, $b->id] <=> [$a->createdAt, $a->id]);
        $pagination = new Pagination(count($sorted), $perPage, $page);

        return $pagination->withItems(array_slice($sorted, $pagination->offset(), $perPage));
    }

    public function create(?int $authorId, string $body, string $createdAt): int
    {
        $id = count($this->announcements) + 1;
        $this->announcements[] = new Announcement($id, $body, $createdAt, $authorId === null ? null : 'staff');

        return $id;
    }

    public function lastReadAt(int $userId): ?string
    {
        return $this->readAt[$userId] ?? null;
    }

    public function markRead(int $userId, string $readAt): void
    {
        $this->readAt[$userId] = $readAt;
    }

    public function countUnread(int $userId): int
    {
        $readAt = $this->lastReadAt($userId);

        return count(array_filter($this->announcements, fn (Announcement $a) => $a->isNewerThan($readAt)));
    }
}
