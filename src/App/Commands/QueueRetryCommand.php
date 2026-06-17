<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\ConsoleCommand;
use Core\Attribute\Option;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Database\Database;
use Core\Queue\DatabaseQueue;

/**
 * Re-enqueue failed jobs so a worker will process them again.
 *
 * Usage:
 *   php console queue:retry            # retry all failed jobs
 *   php console queue:retry --id=<id>  # retry one
 */
#[ConsoleCommand(
    name: 'queue:retry',
    description: 'Re-enqueue failed queue jobs'
)]
class QueueRetryCommand implements CommandInterface
{
    public function __construct(
        private readonly Database $db
    ) {}

    public function execute(
        OutputInterface $output,
        #[Option('id', 'i', 'Id of a single failed job to retry', '')]
        string $id = ''
    ): int {
        $queue = new DatabaseQueue($this->db);

        if ($id !== '') {
            if ($queue->retryFailed($id)) {
                $output->success("Re-enqueued failed job: {$id}");
                return 0;
            }

            $output->error("No failed job found with id: {$id}");
            return 1;
        }

        $count = $queue->retryAllFailed();
        $output->success("Re-enqueued {$count} failed job(s).");

        return 0;
    }
}
