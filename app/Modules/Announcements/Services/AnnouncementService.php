<?php

declare(strict_types=1);

namespace App\Modules\Announcements\Services;

use App\Contracts\ClockInterface;
use App\Modules\Announcements\Contracts\AnnouncementRepositoryInterface;
use App\Modules\Auth\Models\User;
use App\Support\Pagination;

final class AnnouncementService
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcements,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * The player's feed. Viewing it marks everything as read.
     *
     * @return array{announcements: Pagination, lastReadAt: ?string, hadUnread: bool}
     */
    public function feedFor(User $player, int $page = 1): array
    {
        $lastReadAt = $this->announcements->lastReadAt($player->id);
        $hadUnread = $this->announcements->countUnread($player->id) > 0;

        if ($hadUnread) {
            $this->announcements->markRead($player->id, $this->now());
        }

        return [
            'announcements' => $this->announcements->paginate($page, self::PER_PAGE),
            'lastReadAt' => $lastReadAt,
            'hadUnread' => $hadUnread,
        ];
    }

    public function unreadCount(User $player): int
    {
        return $this->announcements->countUnread($player->id);
    }

    public function latest(int $limit = 10): Pagination
    {
        return $this->announcements->paginate(1, $limit);
    }

    public function post(User $author, string $body): void
    {
        $this->announcements->create($author->id, $body, $this->now());
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
