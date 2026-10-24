<?php

declare(strict_types=1);

namespace App\Modules\Events\Contracts;

use App\Support\Pagination;

interface EventRepositoryInterface
{
    public function add(int $userId, string $body, string $createdAt): void;

    /** Items: arrays with id, body, created_at, read_at. */
    public function paginateFor(int $userId, int $page, int $perPage): Pagination;

    public function countUnread(int $userId): int;

    public function markAllRead(int $userId, string $readAt): void;

    public function deleteAll(int $userId): void;
}
