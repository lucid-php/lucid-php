<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\ConsoleCommand;
use Core\Config\Config;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Container;
use Core\Schedule\ScheduledJobInterface;
use DateTimeImmutable;
use DateTimeZone;

#[ConsoleCommand(
    name: 'schedule:list',
    description: 'List all scheduled tasks with their next run times'
)]
class ScheduleListCommand implements CommandInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly Config $config
    ) {
    }

    public function execute(OutputInterface $output): int
    {
        $timezone = new DateTimeZone($this->config->get('schedule.timezone', 'UTC'));
        $jobClasses = $this->config->get('schedule.tasks', []);

        if (empty($jobClasses)) {
            $output->warning('No scheduled tasks registered in config/schedule.php');
            return 0;
        }

        $output->info('Scheduled Tasks');
        $output->writeln('<dim>' . str_repeat('-', 80) . '</dim>');
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
                $output->writeln('   Class:     <comment>' . $jobClass . '</comment>');
                $output->writeln("   Schedule:  <comment>{$cronExpression}</comment>");
                $output->writeln("   Next run:  <comment>{$nextRun->format('Y-m-d H:i:s T')}</comment>");
                $output->writeln("   In:        <comment>{$hoursUntil}h {$minutesUntil}m</comment>");
                $output->writeln('');

            } catch (\Throwable $e) {
                $output->error("Error loading {$jobClass}: {$e->getMessage()}");
                $output->writeln('');
            }
        }

        $output->writeln('<dim>' . str_repeat('-', 80) . '</dim>');
        $output->success('Total tasks: ' . count($jobClasses));

        return 0;
    }
}
