<?php

declare(strict_types=1);

namespace App\Job;

use Core\Schedule\CronExpression;
use Core\Schedule\ScheduledJobInterface;

/**
 * Example Scheduled Job - Send Daily Summary
 * 
 * This job runs every day at 8 AM to send a daily summary email.
 * No dependencies required - demonstrates simple scheduled task.
 */
class SendDailySummaryJob implements ScheduledJobInterface
{
    public function schedule(): string
    {
        // Run every day at 8:00 AM
        return CronExpression::dailyAt(8, 0);
    }

    public function execute(): void
    {
        echo "Starting daily summary job...\n";
        
        // Example: In a real implementation, you would:
        // 1. Query the database for statistics
        // 2. Generate the summary report
        // 3. Send via email using MailerInterface
        // 4. Store in database or cache
        
        echo "Daily summary: 0 new users in the last 24 hours\n";
        echo "Daily summary job completed.\n";
    }

    public function getDescription(): string
    {
        return 'Send daily summary email';
    }
}
