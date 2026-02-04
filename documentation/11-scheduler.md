# Scheduler (Cron-like)

The scheduler follows the framework's zero-magic philosophy with explicit task scheduling.

## Philosophy

- **No auto-discovery** - Tasks must be explicitly registered in `config/schedule.php`
- **Standard cron expressions** - No DSL or magic syntax
- **Explicit execution** - Run via command, typically from system cron
- **Typed interfaces** - Every job implements `ScheduledJobInterface`

## Quick Start

### 1. Create a scheduled job

```php
<?php

declare(strict_types=1);

namespace App\Job;

use Core\Schedule\CronExpression;
use Core\Schedule\ScheduledJobInterface;

class BackupDatabaseJob implements ScheduledJobInterface
{
    public function __construct(
        private readonly Database $db
    ) {}

    public function schedule(): string
    {
        // Run every day at 3 AM
        return CronExpression::dailyAt(3);
    }

    public function execute(): void
    {
        // Your backup logic here
        echo "Backing up database...\n";
    }

    public function getDescription(): string
    {
        return 'Backup database daily at 3 AM';
    }
}
```

### 2. Register the job in `config/schedule.php`

```php
return [
    'timezone' => 'UTC',
    'tasks' => [
        App\Job\BackupDatabaseJob::class,
        App\Job\SendDailySummaryJob::class,
    ],
];
```

### 3. Add to system cron

```bash
* * * * * cd /path/to/project && php console schedule:run >> /dev/null 2>&1
```

The scheduler runs every minute and executes tasks that are due.

## Available Commands

### schedule:list

List all registered scheduled tasks with their next run times:

```bash
php console schedule:list

# Output:
# Scheduled Tasks
# --------------------------------------------------------------------------------
# 
# 1. Backup database daily at 3 AM
#    Class:     App\Job\BackupDatabaseJob
#    Schedule:  0 3 * * *
#    Next run:  2026-02-05 03:00:00 UTC
#    In:        16h 33m
# 
# 2. Send daily summary email
#    Class:     App\Job\SendDailySummaryJob
#    Schedule:  0 8 * * *
#    Next run:  2026-02-05 08:00:00 UTC
#    In:        21h 33m
```

### schedule:run

Execute all tasks that are currently due:

```bash
# Run scheduled tasks
php console schedule:run

# With verbose output
php console schedule:run -v
php console schedule:run --verbose

# Output:
# Registered: Backup database daily at 3 AM
#   Schedule: 0 3 * * *
#   Next run: 2026-02-05 03:00:00 UTC
# 
# Running scheduled tasks...
# 
# ✓ Completed: 1 ran, 0 skipped, 0 failed
```

## Cron Expressions

Use standard 5-part cron syntax:

```
* * * * *
| | | | |
| | | | +---- Day of week (0-7, Sunday=0 or 7)
| | | +------ Month (1-12)
| | +-------- Day of month (1-31)
| +---------- Hour (0-23)
+------------ Minute (0-59)
```

**Examples:**

```php
// Every minute
'* * * * *'

// Every 5 minutes
'*/5 * * * *'

// Every day at 3 AM
'0 3 * * *'

// Every Monday at 9 AM
'0 9 * * 1'

// First day of month at midnight
'0 0 1 * *'

// Every weekday at 6 PM
'0 18 * * 1-5'
```

## Helper Methods

The `CronExpression` class provides explicit helper methods:

```php
use Core\Schedule\CronExpression;

class MyJob implements ScheduledJobInterface
{
    public function schedule(): string
    {
        // Common patterns
        return CronExpression::everyMinute();         // * * * * *
        return CronExpression::everyFiveMinutes();    // */5 * * * *
        return CronExpression::everyTenMinutes();     // */10 * * * *
        return CronExpression::everyFifteenMinutes(); // */15 * * * *
        return CronExpression::everyThirtyMinutes();  // */30 * * * *
        return CronExpression::hourly();              // 0 * * * *
        return CronExpression::hourlyAt(15);          // 15 * * * *
        return CronExpression::daily();               // 0 0 * * *
        return CronExpression::dailyAt(3, 30);        // 30 3 * * *
        return CronExpression::weekly();              // 0 0 * * 0
        return CronExpression::weeklyOn(1, 9, 0);     // 0 9 * * 1 (Monday 9 AM)
        return CronExpression::monthly();             // 0 0 1 * *
        return CronExpression::monthlyOn(15, 12);     // 0 12 15 * * (15th at noon)
        
        // Or use explicit cron syntax
        return '30 14 * * *'; // Every day at 2:30 PM
    }
}
```

## Scheduled Job Interface

All scheduled jobs must implement `ScheduledJobInterface`:

```php
interface ScheduledJobInterface
{
    /**
     * Define when this job should run (cron expression)
     */
    public function schedule(): string;

    /**
     * Execute the job
     */
    public function execute(): void;

    /**
     * Get job description (for logging and display)
     */
    public function getDescription(): string;
}
```

## Complete Example

```php
<?php

declare(strict_types=1);

namespace App\Job;

use Core\Schedule\CronExpression;
use Core\Schedule\ScheduledJobInterface;
use Core\Database\Database;
use Core\Mail\MailerInterface;

class SendWeeklyReportJob implements ScheduledJobInterface
{
    public function __construct(
        private readonly Database $db,
        private readonly MailerInterface $mailer
    ) {}

    public function schedule(): string
    {
        // Every Monday at 9 AM
        return CronExpression::weeklyOn(1, 9, 0);
    }

    public function execute(): void
    {
        // Query database for weekly stats
        $stats = $this->db->query("
            SELECT COUNT(*) as new_users 
            FROM users 
            WHERE created_at >= date('now', '-7 days')
        ");

        $newUsers = $stats[0]['new_users'] ?? 0;

        // Send report email
        $this->mailer->send(
            to: 'admin@example.com',
            subject: 'Weekly Report',
            body: "New users this week: {$newUsers}"
        );
    }

    public function getDescription(): string
    {
        return 'Send weekly report every Monday at 9 AM';
    }
}
```

**Register in `config/schedule.php`:**

```php
return [
    'timezone' => 'America/New_York', // Set your timezone
    'tasks' => [
        App\Job\SendWeeklyReportJob::class,
    ],
];
```

## Production Setup

### 1. Add to system crontab

```bash
# Open crontab
crontab -e

# Add entry (runs every minute)
* * * * * cd /var/www/your-app && php console schedule:run >> /dev/null 2>&1
```

### 2. Or use systemd timer (Linux)

Create `/etc/systemd/system/scheduler.service`:
```ini
[Unit]
Description=Framework Scheduler

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/var/www/your-app
ExecStart=/usr/bin/php console schedule:run
```

Create `/etc/systemd/system/scheduler.timer`:
```ini
[Unit]
Description=Run Framework Scheduler every minute

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min

[Install]
WantedBy=timers.target
```

Enable and start:
```bash
sudo systemctl enable scheduler.timer
sudo systemctl start scheduler.timer
```

## Philosophy Compliance

The scheduler system follows all framework principles:

✅ **No Magic**
- Tasks explicitly registered in config (no directory scanning)
- No "magic" scheduling DSL - uses standard cron expressions
- No hidden task registry or discovery

✅ **Strict Typing**
- All jobs implement `ScheduledJobInterface`
- Cron expressions are strings (standard format)
- Return values and parameters are typed

✅ **Explicit Over Convenient**
- Must explicitly register each task in config
- Must explicitly run via command (no automatic background execution)
- No "magic" time formats - use standard cron syntax

✅ **Traceable**
- Command+Click on job class in config
- Command+Click on `ScheduledJobInterface`
- Clear execution path: cron → console → ScheduleRunCommand → Scheduler → Job

✅ **Dependencies Are Explicit**
- Jobs receive dependencies via constructor injection
- Container resolves dependencies transparently
- No global state or singletons
