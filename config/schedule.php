<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Scheduler Timezone
    |--------------------------------------------------------------------------
    |
    | The timezone used for evaluating cron expressions.
    | All scheduled tasks will run based on this timezone.
    |
    */
    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Scheduled Tasks
    |--------------------------------------------------------------------------
    |
    | Register your scheduled task classes here.
    | Each class must implement ScheduledJobInterface.
    |
    | Tasks are explicitly registered - no auto-discovery.
    |
    */
    'tasks' => [
        // Example tasks (uncomment to use):
        App\Job\CleanupOldLogsJob::class,
        App\Job\SendDailySummaryJob::class,
    ],
];
