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
 *
 * Because jobs run inline, exceptions propagate to the caller (no retry / failed
 * store). The acknowledge methods are no-ops: there is nothing to persist.
 */
class SyncQueue implements QueueInterface
{
    public function __construct(
        private readonly Container $container
    ) {}

    public function push(object $job, string $queue = 'default', int $delaySeconds = 0): void
    {
        // Execute immediately (delay is ignored in sync mode). Exceptions
        // propagate to the caller — there is no background retry here.
        $worker = new QueueWorker($this, $this->container);
        $worker->runJob($job);
    }

    public function pop(string $queue = 'default'): ?QueuedJob
    {
        // Sync queue never has jobs waiting
        return null;
    }

    public function delete(QueuedJob $job): void
    {
        // No-op: nothing was persisted.
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): void
    {
        // No-op: nothing was persisted.
    }

    public function fail(QueuedJob $job, string $exception): void
    {
        // No-op: nothing was persisted.
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
