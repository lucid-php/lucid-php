<?php

declare(strict_types=1);

namespace Core\Schedule;

use DateTimeImmutable;

/**
 * ScheduledTask - Represents a single scheduled task
 * 
 * Immutable task definition with explicit cron expression
 */
readonly class ScheduledTask
{
    public function __construct(
        private string $description,
        private string $cronExpression,
        private \Closure $callback
    ) {}

    /**
     * Check if this task should run at the given time
     */
    public function isDue(DateTimeImmutable $now): bool
    {
        return CronExpression::matches($this->cronExpression, $now);
    }

    /**
     * Execute the task
     */
    public function execute(): void
    {
        ($this->callback)();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCronExpression(): string
    {
        return $this->cronExpression;
    }

    /**
     * Get next run time
     */
    public function getNextRunTime(DateTimeImmutable $from): DateTimeImmutable
    {
        return CronExpression::getNextRunDate($this->cronExpression, $from);
    }
}
