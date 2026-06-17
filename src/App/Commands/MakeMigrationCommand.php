<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\Argument;
use Core\Attribute\ConsoleCommand;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;

/**
 * Scaffold an empty pair of raw-SQL migration files.
 *
 * Usage:
 *   php console make:migration create_widgets_table
 *
 * Creates database/migrations/<timestamp>_<name>.up.sql and .down.sql.
 */
#[ConsoleCommand(
    name: 'make:migration',
    description: 'Create a new pair of .up.sql / .down.sql migration files'
)]
class MakeMigrationCommand implements CommandInterface
{
    public function execute(
        OutputInterface $output,
        #[Argument('name', 'Migration name, e.g. create_widgets_table')]
        string $name = ''
    ): int {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', trim($name)) ?? '');
        $slug = trim($slug, '_');

        if ($slug === '') {
            $output->error('Provide a migration name, e.g. make:migration create_widgets_table');
            return 1;
        }

        $dir = dirname(__DIR__, 3) . '/database/migrations';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $prefix = date('YmdHis') . '_' . $slug;
        $up = "{$dir}/{$prefix}.up.sql";
        $down = "{$dir}/{$prefix}.down.sql";

        file_put_contents($up, "-- Migration: {$slug} (up)\n-- Write the forward SQL here.\n");
        file_put_contents($down, "-- Migration: {$slug} (down)\n-- Write the SQL that reverses the up migration here.\n");

        $output->success('Created migration files:');
        $output->writeln('  <comment>' . $up . '</comment>');
        $output->writeln('  <comment>' . $down . '</comment>');

        return 0;
    }
}
