<?php

declare(strict_types=1);

namespace Core\Pagination;

/**
 * Explicit pagination calculator.
 * 
 * No magic - just math. Takes total count, page number, and items per page,
 * then calculates offset and generates metadata.
 * 
 * Philosophy-compliant because:
 * - Not a global helper function
 * - Explicit instantiation (new Paginator(...))
 * - No hidden behavior - just calculations
 * - Returns plain arrays/values
 * 
 * Usage:
 *   $paginator = new Paginator(total: 100, page: 2, perPage: 20);
 *   $offset = $paginator->getOffset();  // 20
 *   $users = $repo->findAll($paginator->getPerPage(), $paginator->getOffset());
 *   
 *   return Response::json([
 *       'data' => $users,
 *       'pagination' => $paginator->getMetadata()
 *   ]);
 */
class Paginator
{
    private int $total;
    private int $currentPage;
    private int $perPage;
    private int $lastPage;
    private int $offset;

    public function __construct(int $total, int $page = 1, int $perPage = 20)
    {
        // Validate inputs
        if ($total < 0) {
            throw new \InvalidArgumentException('Total must be non-negative');
        }
        if ($page < 1) {
            $page = 1;
        }
        if ($perPage < 1) {
            $perPage = 20;
        }

        $this->total = $total;
        $this->perPage = $perPage;
        $this->lastPage = (int) ceil($total / $perPage) ?: 1;
        
        // Clamp page to valid range
        if ($page > $this->lastPage) {
            $page = $this->lastPage;
        }
        
        $this->currentPage = $page;
        $this->offset = ($page - 1) * $perPage;
    }

    /**
     * Get database offset (LIMIT x, OFFSET y)
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Get items per page (LIMIT x)
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get current page number
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get total number of items
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Get last page number
     */
    public function getLastPage(): int
    {
        return $this->lastPage;
    }

    /**
     * Get the first item number on current page (1-indexed)
     */
    public function getFrom(): int
    {
        return $this->total === 0 ? 0 : $this->offset + 1;
    }

    /**
     * Get the last item number on current page (1-indexed)
     */
    public function getTo(): int
    {
        $to = $this->offset + $this->perPage;
        return min($to, $this->total);
    }

    /**
     * Check if there's a previous page
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Check if there's a next page
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    /**
     * Get previous page number (or null if none)
     */
    public function getPreviousPage(): ?int
    {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }

    /**
     * Get next page number (or null if none)
     */
    public function getNextPage(): ?int
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    /**
     * Get all metadata as an array
     * 
     * Returns standard pagination metadata structure suitable for JSON APIs
     */
    public function getMetadata(): array
    {
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => $this->lastPage,
            'from' => $this->getFrom(),
            'to' => $this->getTo(),
            'has_previous' => $this->hasPreviousPage(),
            'has_next' => $this->hasNextPage(),
            'previous_page' => $this->getPreviousPage(),
            'next_page' => $this->getNextPage(),
        ];
    }

    /**
     * Check if the current page is empty (beyond available data)
     */
    public function isEmpty(): bool
    {
        return $this->total === 0;
    }
}
