<?php

declare(strict_types=1);

namespace App\Modules\Announcements\Models;

final class Announcement
{
    public function __construct(
        public readonly int $id,
        public readonly string $body,
        public readonly string $createdAt,
        public readonly ?string $authorName = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            body: (string) $row['body'],
            createdAt: (string) $row['created_at'],
            authorName: $row['author_name'] ?? null,
        );
    }

    public function isNewerThan(?string $readAt): bool
    {
        return $readAt === null || $this->createdAt > $readAt;
    }
}
