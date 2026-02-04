<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Container;

/**
 * Synchronous Queue (No Queue)
 * 
 * Executes jobs immediately instead of queuing them.
 * Perfect for:
 * - Local development
 * - Testing
 * - Environments without queue workers
 * 
 * Philosophy:
 * - No magic - you see exactly when jobs execute
 * - Explicit - jobs run inline, not in background
 */
class SyncQueue implements QueueInterface
{
    public function __construct(
        private final Container $container
    ) {}

    public function push(object $job, string $queue = 'default'): void
    {
        // Execute immediately instead of queuing
        $worker = new QueueWorker($this, $this->container);
        
        $queuedJob = new QueuedJob(
            id: 'sync-' . uniqid(),
            job: $job,
            queue: $queue,
            attempts: 0,
            availableAt: time(),
        );
        
        $worker->processJob($queuedJob);
    }

    public function pop(string $queue = 'default'): ?QueuedJob
    {
        // Sync queue never has jobs waiting
        return null;
    }

    public function size(string $queue = 'default'): int
    {
        // Sync queue is always empty
        return 0;
    }

    public function clear(string $queue = 'default'): void
    {
        // Nothing to clear
    }
}
