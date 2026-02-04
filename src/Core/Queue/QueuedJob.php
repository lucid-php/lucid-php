<?php

declare(strict_types=1);

namespace Core\Queue;

/**
 * Queued Job Container
 * 
 * Wraps a job with metadata for queue processing
 */
readonly class QueuedJob
{
    public function __construct(
        public string $id,
        public object $job,
        public string $queue,
        public int $attempts,
        public int $availableAt,
    ) {}
}
