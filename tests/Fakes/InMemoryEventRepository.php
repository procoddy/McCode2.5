<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Modules\Events\Contracts\EventRepositoryInterface;
use App\Support\Pagination;

final class InMemoryEventRepository implements EventRepositoryInterface
{
    /** @var list<array{id: int, user_id: int, body: string, created_at: string, read_at: ?string}> */
    public array $events = [];

    public function add(int $userId, string $body, string $createdAt): void
    {
        $this->events[] = ['id' => count($this->events) + 1, 'user_id' => $userId, 'body' => $body, 'created_at' => $createdAt, 'read_at' => null];
    }

    public function paginateFor(int $userId, int $page, int $perPage): Pagination
    {
        $mine = array_values(array_reverse($this->for($userId)));
        $pagination = new Pagination(count($mine), $perPage, $page);

        return $pagination->withItems(array_slice($mine, $pagination->offset(), $perPage));
    }

    public function countUnread(int $userId): int
    {
        return count(array_filter($this->for($userId), fn ($e) => $e['read_at'] === null));
    }

    public function markAllRead(int $userId, string $readAt): void
    {
        foreach ($this->events as &$event) {
            if ($event['user_id'] === $userId && $event['read_at'] === null) {
                $event['read_at'] = $readAt;
            }
        }
    }

    public function deleteAll(int $userId): void
    {
        $this->events = array_values(array_filter($this->events, fn ($e) => $e['user_id'] !== $userId));
    }

    private function for(int $userId): array
    {
        return array_filter($this->events, fn ($e) => $e['user_id'] === $userId);
    }
}
