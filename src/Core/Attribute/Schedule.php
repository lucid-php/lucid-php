<?php

declare(strict_types=1);

namespace Core\Attribute;

use Attribute;

/**
 * Schedule Attribute - Mark a method as a scheduled task
 * 
 * This is metadata only. Tasks must be explicitly registered with the Scheduler.
 * No auto-discovery. No magic.
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class Schedule
{
    public function __construct(
        public string $cron,
        public string $description = ''
    ) {}
}
