<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attributes\ConsoleCommand;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Schedule\ScheduledJobInterface;
use Core\Container;
use Core\Config\Config;
use DateTimeZone;
use DateTimeImmutable;

#[ConsoleCommand(
    name: 'schedule:list',
    description: 'List all scheduled tasks with their next run times'
)]
class ScheduleListCommand implements CommandInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly Config $config
    ) {}

    public function execute(OutputInterface $output): int
    {
        $timezone = new DateTimeZone($this->config->get('schedule.timezone', 'UTC'));
        $jobClasses = $this->config->get('schedule.tasks', []);

        if (empty($jobClasses)) {
            $output->warning('No scheduled tasks registered in config/schedule.php');
            return 0;
        }

        $output->info('Scheduled Tasks');
        $output->writeln(str_repeat('-', 80));
        $output->writeln('');

        $now = new DateTimeImmutable('now', $timezone);

        foreach ($jobClasses as $i => $jobClass) {
            try {
                $job = $this->container->get($jobClass);

                if (!$job instanceof ScheduledJobInterface) {
                    $output->error("Class {$jobClass} must implement ScheduledJobInterface");
                    continue;
                }

                $cronExpression = $job->schedule();
                $description = $job->getDescription();

                // Calculate next run time
                $nextRun = \Core\Schedule\CronExpression::getNextRunDate($cronExpression, $now);
                $timeUntil = $nextRun->getTimestamp() - $now->getTimestamp();
                $hoursUntil = floor($timeUntil / 3600);
                $minutesUntil = floor(($timeUntil % 3600) / 60);

                $output->writeln(($i + 1) . ". {$description}");
                $output->writeln("   Class:     " . $jobClass);
                $output->writeln("   Schedule:  {$cronExpression}");
                $output->writeln("   Next run:  {$nextRun->format('Y-m-d H:i:s T')}");
                $output->writeln("   In:        {$hoursUntil}h {$minutesUntil}m");
                $output->writeln('');

            } catch (\Throwable $e) {
                $output->error("Error loading {$jobClass}: {$e->getMessage()}");
                $output->writeln('');
            }
        }

        $output->writeln(str_repeat('-', 80));
        $output->success('Total tasks: ' . count($jobClasses));

        return 0;
    }
}
