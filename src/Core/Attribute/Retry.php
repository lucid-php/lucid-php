<?php

declare(strict_types=1);

namespace Core\Attribute;

use Attribute;

/**
 * Opt a queue job into automatic retries.
 *
 * Metadata only — {@see \Core\Queue\QueueWorker} reads it. Absent the attribute,
 * a job is tried exactly once then moved to failed_jobs (no hidden retries).
 *
 *   #[Retry(times: 3, backoff: 10)]  // up to 3 attempts, 10s between them
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Retry
{
    public function __construct(
        public int $times = 1,
        public int $backoff = 0,
    ) {
    }
}
