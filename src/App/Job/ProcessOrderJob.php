<?php

declare(strict_types=1);

namespace App\Job;

/**
 * Process Order Job
 * 
 * Example job showing:
 * - Multiple constructor parameters (job data)
 * - No dependencies needed in handle()
 * - Pure processing logic
 */
readonly class ProcessOrderJob
{
    public function __construct(
        public int $orderId,
        public float $total,
        /** @var array<int, array{productId: int, quantity: int}> */
        public array $items,
    ) {}

    /**
     * Execute the job
     */
    public function handle(): void
    {
        // Process order (update inventory, send notifications, etc.)
        error_log(sprintf(
            "🛒 [QUEUED] Processing order #%d (Total: $%.2f, Items: %d)",
            $this->orderId,
            $this->total,
            count($this->items)
        ));
    }
}
