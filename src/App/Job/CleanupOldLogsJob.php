<?php

declare(strict_types=1);

namespace App\Job;

use Core\Schedule\CronExpression;
use Core\Schedule\ScheduledJobInterface;

/**
 * Example Scheduled Job - Clean up old logs
 * 
 * This job runs daily at 3 AM to remove logs older than 30 days.
 * No dependencies required - demonstrates simple scheduled task.
 */
class CleanupOldLogsJob implements ScheduledJobInterface
{
    public function schedule(): string
    {
        // Run daily at 3 AM
        return CronExpression::dailyAt(3);
    }

    public function execute(): void
    {
        // Example cleanup logic
        $logDir = __DIR__ . '/../../../storage/logs';
        $threshold = time() - (30 * 24 * 60 * 60); // 30 days ago
        
        if (!is_dir($logDir)) {
            echo "Log directory not found, skipping cleanup.\n";
            return;
        }
        
        $deleted = 0;
        foreach (glob($logDir . '/*.log') as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $deleted++;
            }
        }
        
        echo "Cleanup completed. Deleted {$deleted} old log files.\n";
    }

    public function getDescription(): string
    {
        return 'Clean up logs older than 30 days';
    }
}
