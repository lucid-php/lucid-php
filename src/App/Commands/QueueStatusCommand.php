<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\ConsoleCommand;
use Core\Config\Config;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Database\Database;

/**
 * Show pending and failed job counts (database queue driver).
 *
 * Usage:
 *   php console queue:status
 */
#[ConsoleCommand(
    name: 'queue:status',
    description: 'Show pending and failed queue job counts'
)]
class QueueStatusCommand implements CommandInterface
{
    public function __construct(
        private readonly Database $db,
        private readonly Config $config
    ) {}

    public function execute(OutputInterface $output): int
    {
        $driver = $this->config->getString('queue.driver', 'sync');

        if ($driver !== 'database') {
            $output->info("Queue driver is '{$driver}'; no persistent jobs to report.");
            return 0;
        }

        try {
            $pending = $this->db->query(
                "SELECT queue, COUNT(*) AS c FROM jobs WHERE reserved_at IS NULL GROUP BY queue ORDER BY queue"
            );
            $failed = (int) $this->db->query("SELECT COUNT(*) AS c FROM failed_jobs")[0]['c'];
        } catch (\Throwable $e) {
            $output->error('Could not read queue tables (have you run migrations?): ' . $e->getMessage());
            return 1;
        }

        if ($pending === []) {
            $output->info('No pending jobs.');
        } else {
            $rows = array_map(fn(array $row): array => [$row['queue'], $row['c']], $pending);
            $output->table(['Queue', 'Pending'], $rows);
        }

        $output->writeln('Failed jobs: <comment>' . $failed . '</comment>');

        return 0;
    }
}
