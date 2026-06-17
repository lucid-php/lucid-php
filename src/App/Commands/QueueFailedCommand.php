<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\ConsoleCommand;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Database\Database;
use Core\Queue\DatabaseQueue;

/**
 * List jobs that have exhausted their retries and moved to failed_jobs.
 *
 * Usage:
 *   php console queue:failed
 */
#[ConsoleCommand(
    name: 'queue:failed',
    description: 'List failed queue jobs'
)]
class QueueFailedCommand implements CommandInterface
{
    public function __construct(
        private readonly Database $db
    ) {}

    public function execute(OutputInterface $output): int
    {
        $queue = new DatabaseQueue($this->db);
        $failed = $queue->failedJobs();

        if ($failed === []) {
            $output->info('No failed jobs.');
            return 0;
        }

        $output->writeln('Failed jobs:');
        $output->writeln('');

        foreach ($failed as $job) {
            $when = date('Y-m-d H:i:s', (int) $job['failed_at']);
            $output->writeln("  <comment>{$job['id']}</comment>  [{$job['queue']}]  attempts={$job['attempts']}  {$when}");
            $output->writeln("    <dim>" . str_replace("\n", ' ', substr((string) $job['exception'], 0, 200)) . "</dim>");
        }

        $output->writeln('');
        $output->writeln('Total: <comment>' . count($failed) . '</comment>');

        return 0;
    }
}
