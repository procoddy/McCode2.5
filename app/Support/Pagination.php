<?php

declare(strict_types=1);

namespace App\Support;

final class Pagination
{
    public readonly int $currentPage;

    public readonly int $lastPage;

    public function __construct(
        public readonly int $total,
        public readonly int $perPage = 25,
        int $currentPage = 1,
        public readonly array $items = [],
    ) {
        $this->lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $this->currentPage = min(max(1, $currentPage), $this->lastPage);
    }

    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    /** @return list<int> Page numbers centred on the current page. */
    public function window(int $size = 5): array
    {
        $start = max(1, $this->currentPage - intdiv($size, 2));
        $end = min($this->lastPage, $start + $size - 1);
        $start = max(1, $end - $size + 1);

        return range($start, $end);
    }

    public function withItems(array $items): self
    {
        return new self($this->total, $this->perPage, $this->currentPage, $items);
    }
}
