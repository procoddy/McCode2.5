<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function test_offset_and_bounds(): void
    {
        $page = new Pagination(total: 95, perPage: 10, currentPage: 3);

        $this->assertSame(20, $page->offset());
        $this->assertSame(10, $page->lastPage);
        $this->assertSame(10, (new Pagination(95, 10, 99))->currentPage);
    }

    public function test_window_stays_within_range(): void
    {
        $this->assertSame([1, 2, 3, 4, 5], (new Pagination(100, 10, 1))->window());
        $this->assertSame([6, 7, 8, 9, 10], (new Pagination(100, 10, 10))->window());
    }
}
