<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attributes\ConsoleCommand;
use Core\Attributes\Option;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Database\Migrator;

#[ConsoleCommand(
    name: 'migrate',
    description: 'Run database migrations'
)]
class MigrateCommand implements CommandInterface
{
    public function __construct(
        private readonly Migrator $migrator
    ) {}

    public function execute(
        OutputInterface $output,
        #[Option('step', 's', 'Number of migrations to run', 0)]
        int $step = 0
    ): int {
        $output->info('Running migrations...');
        $output->writeln('');

        try {
            $migrations = $this->migrator->migrate($step);

            if (empty($migrations)) {
                $output->warning('No migrations to run.');
                return 0;
            }

            foreach ($migrations as $migration) {
                $output->success("Migrated: {$migration}");
            }

            $output->writeln('');
            $output->success('All migrations completed successfully.');
            return 0;
        } catch (\Throwable $e) {
            $output->error('Migration failed: ' . $e->getMessage());
            return 1;
        }
    }
}
