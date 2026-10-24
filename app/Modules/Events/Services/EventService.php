<?php

declare(strict_types=1);

namespace App\Modules\Events\Services;

use App\Contracts\ClockInterface;
use App\Modules\Events\Contracts\EventRepositoryInterface;

/**
 * Legacy event_add(): short plain-text notifications shown on the Events page.
 */
final class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly ClockInterface $clock,
    ) {
    }

    public function add(int $userId, string $text): void
    {
        $this->events->add($userId, $text, $this->now());
    }

    public function unreadCount(int $userId): int
    {
        return $this->events->countUnread($userId);
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
