<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attributes\ConsoleCommand;
use Core\Attributes\Option;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Schedule\Scheduler;
use Core\Schedule\ScheduledTask;
use Core\Schedule\ScheduledJobInterface;
use Core\Container;
use Core\Config\Config;
use DateTimeZone;
use DateTimeImmutable;

#[ConsoleCommand(
    name: 'schedule:run',
    description: 'Run scheduled tasks that are due'
)]
class ScheduleRunCommand implements CommandInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly Config $config
    ) {}

    public function execute(
        OutputInterface $output,
        #[Option('verbose', 'v', 'Show detailed output')]
        bool $verbose = false
    ): int {
        $timezone = new DateTimeZone($this->config->get('schedule.timezone', 'UTC'));
        $scheduler = new Scheduler($output, $timezone);

        // Get registered scheduled jobs from config
        $jobClasses = $this->config->get('schedule.tasks', []);

        if (empty($jobClasses)) {
            $output->warning('No scheduled tasks registered in config/schedule.php');
            return 0;
        }

        // Register each job with the scheduler
        foreach ($jobClasses as $jobClass) {
            $job = $this->container->get($jobClass);

            if (!$job instanceof ScheduledJobInterface) {
                $output->error("Class {$jobClass} must implement ScheduledJobInterface");
                continue;
            }

            $task = new ScheduledTask(
                description: $job->getDescription(),
                cronExpression: $job->schedule(),
                callback: fn() => $job->execute()
            );

            $scheduler->task($task);

            if ($verbose) {
                $now = new DateTimeImmutable('now', $timezone);
                $nextRun = $task->getNextRunTime($now);
                $output->writeln("Registered: {$job->getDescription()}");
                $output->writeln("  Schedule: {$job->schedule()}");
                $output->writeln("  Next run: {$nextRun->format('Y-m-d H:i:s T')}");
                $output->writeln('');
            }
        }

        $output->info('Running scheduled tasks...');
        $output->writeln('');

        $stats = $scheduler->run();

        $output->writeln('');
        $output->success("Completed: {$stats['ran']} ran, {$stats['skipped']} skipped, {$stats['failed']} failed");

        return $stats['failed'] > 0 ? 1 : 0;
    }
}
