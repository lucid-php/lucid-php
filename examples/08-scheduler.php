<?php

declare(strict_types=1);

/**
 * Example 8: Task Scheduler
 * 
 * Demonstrates:
 * - Cron-like task scheduling
 * - Scheduled job classes
 * - Cron expressions
 * - Helper methods (daily, hourly, etc.)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Schedule\Scheduler;
use Core\Schedule\ScheduledTask;
use Core\Schedule\CronExpression;
use Core\Schedule\ScheduledJobInterface;
use Core\Console\OutputInterface;

// ===========================
// Example Scheduled Jobs
// ===========================

// Daily cleanup job
class CleanupOldLogsJob implements ScheduledJobInterface
{
    public function schedule(): string
    {
        return CronExpression::daily(); // Runs at 00:00 every day
    }
    
    public function execute(): void
    {
        echo "[Cleanup] Starting log cleanup...\n";
        
        $logDir = __DIR__ . '/../storage/logs';
        $daysToKeep = 30;
        $cutoffDate = new \DateTimeImmutable("-{$daysToKeep} days");
        
        echo "  Removing logs older than {$cutoffDate->format('Y-m-d')}\n";
        
        // Simulate cleanup
        sleep(1);
        
        echo "  ✓ Cleaned up old logs\n";
    }
    
    public function getDescription(): string
    {
        return "Clean up logs older than 30 days";
    }
}

// Hourly backup job
class BackupDatabaseJob implements ScheduledJobInterface
{
    public function schedule(): string
    {
        return CronExpression::hourly(); // Runs at :00 of every hour
    }
    
    public function execute(): void
    {
        echo "[Backup] Creating database backup...\n";
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = "backup_{$timestamp}.sql";
        
        echo "  Creating: {$backupFile}\n";
        sleep(2);
        
        echo "  ✓ Backup completed\n";
    }
    
    public function getDescription(): string
    {
        return "Create hourly database backup";
    }
}

// Weekly report job
class GenerateWeeklyReportJob implements ScheduledJobInterface
{
    public function schedule(): string
    {
        return CronExpression::weekly(); // Runs every Monday at 00:00
    }
    
    public function execute(): void
    {
        echo "[Report] Generating weekly report...\n";
        
        $startDate = new \DateTimeImmutable('last monday');
        $endDate = new \DateTimeImmutable('last sunday');
        
        echo "  Period: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}\n";
        
        sleep(3);
        
        echo "  ✓ Report generated and emailed\n";
    }
    
    public function getDescription(): string
    {
        return "Generate and send weekly sales report";
    }
}

// Every 5 minutes job
class CheckHealthJob implements ScheduledJobInterface
{
    public function schedule(): string
    {
        return CronExpression::everyFiveMinutes(); // Runs every 5 minutes
    }
    
    public function execute(): void
    {
        echo "[Health] Checking system health...\n";
        
        $checks = [
            'Database' => true,
            'Redis' => true,
            'Disk Space' => true,
            'API Services' => true,
        ];
        
        foreach ($checks as $service => $status) {
            $icon = $status ? '✓' : '✗';
            echo "  {$icon} {$service}\n";
        }
        
        echo "  All systems operational\n";
    }
    
    public function getDescription(): string
    {
        return "Check system health every 5 minutes";
    }
}

// Custom cron expression
class SendReminderEmailsJob implements ScheduledJobInterface
{
    public function schedule(): string
    {
        // Every weekday at 9 AM
        return '0 9 * * 1-5';
    }
    
    public function execute(): void
    {
        echo "[Email] Sending reminder emails...\n";
        
        $count = rand(10, 50);
        echo "  Sending {$count} reminder emails\n";
        
        sleep(2);
        
        echo "  ✓ {$count} emails sent\n";
    }
    
    public function getDescription(): string
    {
        return "Send reminder emails at 9 AM on weekdays";
    }
}

// ===========================
// Example Usage
// ===========================

echo "Task Scheduler Examples:\n";
echo "=======================\n\n";

// ===========================
// Example 1: Helper Methods
// ===========================

echo "=== Example 1: Cron Helper Methods ===\n\n";

echo "Available helper methods:\n";
echo "  - everyMinute()         Run every minute\n";
echo "  - everyFiveMinutes()    Run every 5 minutes\n";
echo "  - hourly()              Run every hour at :00\n";
echo "  - daily()               Run every day at 00:00\n";
echo "  - weekly()              Run every Monday at 00:00\n";
echo "  - monthly()             Run on the 1st of every month at 00:00\n";
echo "  - yearly()              Run on January 1st at 00:00\n\n";

// ===========================
// Example 2: Cron Expressions
// ===========================

echo "=== Example 2: Cron Expressions ===\n\n";

echo "Cron format: * * * * *\n";
echo "             │ │ │ │ │\n";
echo "             │ │ │ │ └─── Day of week (0-6, Sunday = 0)\n";
echo "             │ │ │ └───── Month (1-12)\n";
echo "             │ │ └─────── Day of month (1-31)\n";
echo "             │ └───────── Hour (0-23)\n";
echo "             └─────────── Minute (0-59)\n\n";

echo "Examples:\n";
echo "  '0 2 * * *'        - Every day at 2:00 AM\n";
echo "  '*/15 * * * *'     - Every 15 minutes\n";
echo "  '0 0 * * 0'        - Every Sunday at midnight\n";
echo "  '0 9-17 * * 1-5'   - Every hour from 9 AM to 5 PM on weekdays\n";
echo "  '0 0 1 * *'        - First day of every month at midnight\n";
echo "  '30 8 * * 1'       - Every Monday at 8:30 AM\n\n";

// ===========================
// Example 3: Running the Scheduler
// ===========================

echo "=== Example 3: Running the Scheduler ===\n\n";

echo "Register scheduled jobs:\n";
echo "\$scheduler = new Scheduler();\n";
echo "\$scheduler->addJob(new CleanupOldLogsJob());\n";
echo "\$scheduler->addJob(new BackupDatabaseJob());\n";
echo "\$scheduler->addJob(new CheckHealthJob());\n\n";

echo "Run scheduler command:\n";
echo "php console schedule:run\n\n";

echo "Or set up crontab:\n";
echo "* * * * * php /path/to/console schedule:run >> /dev/null 2>&1\n\n";

// ===========================
// Example 4: Production Setup
// ===========================

echo "=== Example 4: Production Setup ===\n\n";

echo "1. Add jobs to config/schedule.php:\n\n";
echo "return [\n";
echo "    'timezone' => 'UTC',\n";
echo "    'tasks' => [\n";
echo "        CleanupOldLogsJob::class,\n";
echo "        BackupDatabaseJob::class,\n";
echo "        GenerateWeeklyReportJob::class,\n";
echo "    ],\n";
echo "];\n\n";

echo "2. Add to crontab (run every minute):\n";
echo "* * * * * cd /path/to/project && php console schedule:run >> /dev/null 2>&1\n\n";

echo "3. The scheduler will:\n";
echo "   - Check which tasks are due\n";
echo "   - Execute only tasks that should run now\n";
echo "   - Log output to storage/logs/scheduler.log\n\n";

// ===========================
// Example 5: List Scheduled Tasks
// ===========================

echo "=== Example 5: List Scheduled Tasks ===\n\n";

echo "View all scheduled tasks:\n";
echo "php console schedule:list\n\n";

echo "Output:\n";
echo "┌──────────────────────────────────┬───────────┬──────────────────┐\n";
echo "│ Task                             │ Schedule  │ Next Run         │\n";
echo "├──────────────────────────────────┼───────────┼──────────────────┤\n";
echo "│ Clean up old logs                │ 0 0 * * * │ in 8 hours       │\n";
echo "│ Create hourly backup             │ 0 * * * * │ in 15 minutes    │\n";
echo "│ Generate weekly report           │ 0 0 * * 1 │ in 2 days        │\n";
echo "│ Check system health              │ */5 * * * │ in 3 minutes     │\n";
echo "│ Send reminder emails (weekdays)  │ 0 9 * * 1-5 │ in 20 hours   │\n";
echo "└──────────────────────────────────┴───────────┴──────────────────┘\n\n";

// ===========================
// Example 6: Timezone Handling
// ===========================

echo "=== Example 6: Timezone Handling ===\n\n";

echo "Scheduler respects timezone configuration:\n\n";
echo "// config/schedule.php\n";
echo "'timezone' => 'America/New_York',\n\n";

echo "All cron expressions use this timezone\n";
echo "Example: daily() runs at midnight in America/New_York\n\n";

// ===========================
// Best Practices
// ===========================

echo "=== Best Practices ===\n\n";

echo "1. Keep tasks idempotent\n";
echo "   ✓ Can be run multiple times safely\n";
echo "   ✗ Don't assume task only runs once\n\n";

echo "2. Handle failures gracefully\n";
echo "   ✓ Wrap in try/catch\n";
echo "   ✓ Log errors properly\n";
echo "   ✗ Don't let one task crash the scheduler\n\n";

echo "3. Monitor execution time\n";
echo "   ✓ Keep tasks under 1 minute\n";
echo "   ✗ Don't block the scheduler\n\n";

echo "4. Use queues for heavy work\n";
echo "   ✓ Schedule a job that dispatches queue jobs\n";
echo "   ✗ Don't do heavy processing directly\n\n";

echo "5. Test cron expressions\n";
echo "   ✓ Use schedule:list to verify\n";
echo "   ✓ Test with different times\n";
echo "   ✗ Don't guess cron syntax\n";
