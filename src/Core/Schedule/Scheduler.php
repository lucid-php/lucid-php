<?php

declare(strict_types=1);

namespace Core\Schedule;

use Core\Console\OutputInterface;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Scheduler - Executes scheduled tasks based on cron-like expressions
 * 
 * No magic, no auto-discovery. Tasks are explicitly registered.
 */
class Scheduler
{
    /** @var array<ScheduledTask> */
    private array $tasks = [];

    public function __construct(
        private readonly OutputInterface $output,
        private readonly DateTimeZone $timezone
    ) {}

    /**
     * Register a task explicitly
     */
    public function task(ScheduledTask $task): void
    {
        $this->tasks[] = $task;
    }

    /**
     * Run all tasks that are due at this moment
     * 
     * @return array{ran: int, skipped: int, failed: int}
     */
    public function run(): array
    {
        $now = new DateTimeImmutable('now', $this->timezone);
        $stats = ['ran' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($this->tasks as $task) {
            if (!$task->isDue($now)) {
                $stats['skipped']++;
                continue;
            }

            $this->output->info("Running: {$task->getDescription()}");

            try {
                $task->execute();
                $stats['ran']++;
                $this->output->success("✓ Completed: {$task->getDescription()}");
            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->output->error("✗ Failed: {$task->getDescription()}");
                $this->output->error("  Error: {$e->getMessage()}");
            }
        }

        return $stats;
    }

    /**
     * Get all registered tasks
     * 
     * @return array<ScheduledTask>
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }
}
