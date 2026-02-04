<?php

declare(strict_types=1);

namespace Core\Schedule;

/**
 * ScheduledJobInterface - Contract for scheduled jobs
 * 
 * Implement this interface for any job that should be scheduled.
 * Each job defines its own schedule and execution logic.
 */
interface ScheduledJobInterface
{
    /**
     * Define when this job should run
     * 
     * Return a cron expression string
     * You can use CronExpression helper methods:
     * - CronExpression::daily()
     * - CronExpression::hourly()
     * - CronExpression::everyFiveMinutes()
     * Or write your own: "0 3 * * *" (every day at 3 AM)
     */
    public function schedule(): string;

    /**
     * Execute the job
     * 
     * This method will be called when the job is due to run.
     */
    public function execute(): void;

    /**
     * Get job description
     * 
     * Used for logging and display purposes
     */
    public function getDescription(): string;
}
