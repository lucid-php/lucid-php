<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\ConsoleCommand;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Database\Migrator;

/**
 * Show which migrations have been applied and which are pending.
 *
 * Usage:
 *   php console migrate:status
 */
#[ConsoleCommand(
    name: 'migrate:status',
    description: 'Show the status of each migration'
)]
class MigrateStatusCommand implements CommandInterface
{
    public function __construct(
        private readonly Migrator $migrator
    ) {
    }

    public function execute(OutputInterface $output): int
    {
        $status = $this->migrator->status();

        if ($status === []) {
            $output->warning('No migrations found.');
            return 0;
        }

        $output->writeln('Migration status:');
        $output->writeln('');

        foreach ($status as $row) {
            if ($row['applied']) {
                $output->success("  [✓] {$row['migration']}  (batch {$row['batch']})");
            } else {
                $output->writeln("  [ ] {$row['migration']}  <comment>(pending)</comment>");
            }
        }

        return 0;
    }
}
