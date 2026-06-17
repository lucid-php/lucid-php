<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\ConsoleCommand;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Database\Database;
use Core\Database\Migrator;

/**
 * Show database driver, connectivity, and migration status.
 *
 * Usage:
 *   php console db:status
 */
#[ConsoleCommand(
    name: 'db:status',
    description: 'Show database connectivity and migration status'
)]
class DbStatusCommand implements CommandInterface
{
    public function __construct(
        private readonly Database $db,
        private readonly Migrator $migrator
    ) {}

    public function execute(OutputInterface $output): int
    {
        $output->writeln('Driver: <comment>' . $this->db->getDriverName() . '</comment>');

        try {
            $this->db->query('SELECT 1');
            $output->success('Connection: OK');
        } catch (\Throwable $e) {
            $output->error('Connection: FAILED - ' . $e->getMessage());
            return 1;
        }

        try {
            $status = $this->migrator->status();
            $applied = count(array_filter($status, fn(array $row): bool => $row['applied']));
            $pending = count($status) - $applied;

            $output->writeln("Migrations: <comment>{$applied}</comment> applied, <comment>{$pending}</comment> pending");

            if ($pending > 0) {
                $output->warning("There are {$pending} pending migration(s). Run: php console migrate");
            }
        } catch (\Throwable $e) {
            $output->error('Could not read migration status: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
