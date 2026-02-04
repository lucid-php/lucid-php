<?php

declare(strict_types=1);

namespace App\Commands;

use App\Repository\UserRepository;
use Core\Attributes\Argument;
use Core\Attributes\ConsoleCommand;
use Core\Attributes\Option;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;

#[ConsoleCommand(
    name: 'db:seed',
    description: 'Seed the database with test data'
)]
class DatabaseSeedCommand implements CommandInterface
{
    public function __construct(
        private readonly UserRepository $users
    ) {}

    public function execute(
        OutputInterface $output,
        #[Argument('count', 'Number of users to create', required: false)]
        int $count = 10,
        #[Option('force', 'f', 'Skip confirmation', false)]
        bool $force = false
    ): int {
        if (!$force) {
            $output->warning("This will create {$count} test users.");
            $output->writeln('Use --force to skip confirmation.');
            return 0;
        }

        $output->info("Creating {$count} users...");
        $created = [];

        for ($i = 1; $i <= $count; $i++) {
            $name = "Test User {$i}";
            $email = "user{$i}+seed@example.com";
            $password = bin2hex(random_bytes(6));

            $user = $this->users->create($name, $email, $password);
            $created[] = [$user->id, $user->email, 'Created'];

            if ($i % 10 === 0) {
                $output->writeln("  Created {$i}/{$count} users...");
            }
        }

        $output->writeln('');
        $output->table(['ID', 'Email', 'Status'], $created);
        $output->success("Created {$count} users successfully!");

        return 0;
    }
}
