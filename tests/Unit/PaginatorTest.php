<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Pagination\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    public function test_it_calculates_offset_correctly(): void
    {
        $paginator = new Paginator(total: 100, page: 1, perPage: 20);
        $this->assertSame(0, $paginator->getOffset());

        $paginator = new Paginator(total: 100, page: 2, perPage: 20);
        $this->assertSame(20, $paginator->getOffset());

        $paginator = new Paginator(total: 100, page: 3, perPage: 25);
        $this->assertSame(50, $paginator->getOffset());
    }

    public function test_it_calculates_last_page_correctly(): void
    {
        $paginator = new Paginator(total: 100, page: 1, perPage: 20);
        $this->assertSame(5, $paginator->getLastPage());

        $paginator = new Paginator(total: 95, page: 1, perPage: 20);
        $this->assertSame(5, $paginator->getLastPage());

        $paginator = new Paginator(total: 10, page: 1, perPage: 20);
        $this->assertSame(1, $paginator->getLastPage());
    }

    public function test_it_handles_zero_total(): void
    {
        $paginator = new Paginator(total: 0, page: 1, perPage: 20);
        
        $this->assertSame(0, $paginator->getTotal());
        $this->assertSame(1, $paginator->getCurrentPage());
        $this->assertSame(1, $paginator->getLastPage());
        $this->assertSame(0, $paginator->getOffset());
        $this->assertTrue($paginator->isEmpty());
    }

    public function test_it_clamps_page_to_valid_range(): void
    {
        // Page too high - should clamp to last page
        $paginator = new Paginator(total: 50, page: 100, perPage: 20);
        $this->assertSame(3, $paginator->getCurrentPage());
        $this->assertSame(40, $paginator->getOffset());

        // Page too low - should default to 1
        $paginator = new Paginator(total: 50, page: -5, perPage: 20);
        $this->assertSame(1, $paginator->getCurrentPage());
        $this->assertSame(0, $paginator->getOffset());
    }

    public function test_it_calculates_from_and_to_correctly(): void
    {
        // First page
        $paginator = new Paginator(total: 100, page: 1, perPage: 20);
        $this->assertSame(1, $paginator->getFrom());
        $this->assertSame(20, $paginator->getTo());

        // Middle page
        $paginator = new Paginator(total: 100, page: 3, perPage: 20);
        $this->assertSame(41, $paginator->getFrom());
        $this->assertSame(60, $paginator->getTo());

        // Last page (partial)
        $paginator = new Paginator(total: 95, page: 5, perPage: 20);
        $this->assertSame(81, $paginator->getFrom());
        $this->assertSame(95, $paginator->getTo());

        // Empty
        $paginator = new Paginator(total: 0, page: 1, perPage: 20);
        $this->assertSame(0, $paginator->getFrom());
        $this->assertSame(0, $paginator->getTo());
    }

    public function test_it_detects_previous_page_correctly(): void
    {
        $paginator = new Paginator(total: 100, page: 1, perPage: 20);
        $this->assertFalse($paginator->hasPreviousPage());
        $this->assertNull($paginator->getPreviousPage());

        $paginator = new Paginator(total: 100, page: 2, perPage: 20);
        $this->assertTrue($paginator->hasPreviousPage());
        $this->assertSame(1, $paginator->getPreviousPage());

        $paginator = new Paginator(total: 100, page: 5, perPage: 20);
        $this->assertTrue($paginator->hasPreviousPage());
        $this->assertSame(4, $paginator->getPreviousPage());
    }

    public function test_it_detects_next_page_correctly(): void
    {
        $paginator = new Paginator(total: 100, page: 5, perPage: 20);
        $this->assertFalse($paginator->hasNextPage());
        $this->assertNull($paginator->getNextPage());

        $paginator = new Paginator(total: 100, page: 4, perPage: 20);
        $this->assertTrue($paginator->hasNextPage());
        $this->assertSame(5, $paginator->getNextPage());

        $paginator = new Paginator(total: 100, page: 1, perPage: 20);
        $this->assertTrue($paginator->hasNextPage());
        $this->assertSame(2, $paginator->getNextPage());
    }

    public function test_it_returns_complete_metadata(): void
    {
        $paginator = new Paginator(total: 100, page: 3, perPage: 20);
        $metadata = $paginator->getMetadata();

        $this->assertSame(3, $metadata['current_page']);
        $this->assertSame(20, $metadata['per_page']);
        $this->assertSame(100, $metadata['total']);
        $this->assertSame(5, $metadata['last_page']);
        $this->assertSame(41, $metadata['from']);
        $this->assertSame(60, $metadata['to']);
        $this->assertTrue($metadata['has_previous']);
        $this->assertTrue($metadata['has_next']);
        $this->assertSame(2, $metadata['previous_page']);
        $this->assertSame(4, $metadata['next_page']);
    }

    public function test_it_validates_inputs(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Paginator(total: -1, page: 1, perPage: 20);
    }

    public function test_it_handles_edge_case_with_single_item(): void
    {
        $paginator = new Paginator(total: 1, page: 1, perPage: 20);
        
        $this->assertSame(1, $paginator->getTotal());
        $this->assertSame(1, $paginator->getCurrentPage());
        $this->assertSame(1, $paginator->getLastPage());
        $this->assertSame(0, $paginator->getOffset());
        $this->assertSame(1, $paginator->getFrom());
        $this->assertSame(1, $paginator->getTo());
        $this->assertFalse($paginator->hasPreviousPage());
        $this->assertFalse($paginator->hasNextPage());
    }

    public function test_it_uses_default_per_page_when_invalid(): void
    {
        $paginator = new Paginator(total: 100, page: 1, perPage: 0);
        $this->assertSame(20, $paginator->getPerPage());

        $paginator = new Paginator(total: 100, page: 1, perPage: -10);
        $this->assertSame(20, $paginator->getPerPage());
    }
}
