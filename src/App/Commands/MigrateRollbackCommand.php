<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attributes\ConsoleCommand;
use Core\Attributes\Option;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Database\Migrator;

#[ConsoleCommand(
    name: 'migrate:rollback',
    description: 'Rollback the last database migration'
)]
class MigrateRollbackCommand implements CommandInterface
{
    public function __construct(
        private readonly Migrator $migrator
    ) {}

    public function execute(
        OutputInterface $output,
        #[Option('step', 's', 'Number of migrations to rollback', 1)]
        int $step = 1
    ): int {
        $output->info('Rolling back migrations...');
        $output->writeln('');

        try {
            $migrations = $this->migrator->rollback($step);

            if (empty($migrations)) {
                $output->warning('No migrations to rollback.');
                return 0;
            }

            foreach ($migrations as $migration) {
                $output->success("Rolled back: {$migration}");
            }

            $output->writeln('');
            $output->success('Rollback completed successfully.');
            return 0;
        } catch (\Throwable $e) {
            $output->error('Rollback failed: ' . $e->getMessage());
            return 1;
        }
    }
}
