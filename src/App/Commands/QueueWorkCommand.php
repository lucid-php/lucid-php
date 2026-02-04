<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attributes\ConsoleCommand;
use Core\Attributes\Option;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Queue\QueueInterface;
use Core\Queue\QueueWorker;
use Core\Container;

/**
 * Queue Work Command
 * 
 * Starts a queue worker that continuously processes jobs.
 * 
 * Usage:
 *   php console queue:work
 *   php console queue:work --queue=emails
 *   php console queue:work --sleep=5
 */
#[ConsoleCommand(
    name: 'queue:work',
    description: 'Process jobs from the queue'
)]
class QueueWorkCommand implements CommandInterface
{
    public function __construct(
        private final QueueInterface $queue,
        private final Container $container
    ) {}

    public function execute(
        OutputInterface $output,
        #[Option('queue', 'q', 'Queue name to process', 'default')]
        string $queue = 'default',
        #[Option('sleep', 's', 'Seconds to sleep when queue is empty', 3)]
        int $sleep = 3
    ): int
    {
        $output->info("Starting queue worker...");
        $output->writeln("Queue: <comment>$queue</comment>");
        $output->writeln("Sleep: <comment>{$sleep}s</comment>");
        $output->writeln("");

        $worker = new QueueWorker($this->queue, $this->container);

        try {
            $worker->work($queue, $sleep);
        } catch (\Throwable $e) {
            $output->error("Worker failed: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
