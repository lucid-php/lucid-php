<?php

declare(strict_types=1);

namespace Core\Queue;

/**
 * Queue Interface
 * 
 * Philosophy:
 * - Explicit job dispatching
 * - Jobs are typed classes, not arrays
 * - No magic serialization/deserialization
 */
interface QueueInterface
{
    /**
     * Push a job onto the queue
     * 
     * @param object $job The job instance to queue
     * @param string $queue Queue name (default: 'default')
     */
    public function push(object $job, string $queue = 'default'): void;

    /**
     * Pop the next job from the queue
     * 
     * @param string $queue Queue name
     * @return QueuedJob|null The next job or null if queue is empty
     */
    public function pop(string $queue = 'default'): ?QueuedJob;

    /**
     * Get the number of jobs in a queue
     */
    public function size(string $queue = 'default'): int;

    /**
     * Clear all jobs from a queue
     */
    public function clear(string $queue = 'default'): void;
}
