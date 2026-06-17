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
 *
 * Lifecycle: push() enqueues; pop() atomically RESERVES the next job (it is not
 * removed until acknowledged). The worker then calls delete() on success,
 * release() to retry later, or fail() to move it to the failed-jobs store.
 * This reserve-then-acknowledge model means a crash mid-processing never loses
 * the job.
 */
interface QueueInterface
{
    /**
     * Push a job onto the queue.
     *
     * @param object $job          The job instance to queue
     * @param string $queue        Queue name (default: 'default')
     * @param int    $delaySeconds Delay before the job becomes available (0 = immediately)
     */
    public function push(object $job, string $queue = 'default', int $delaySeconds = 0): void;

    /**
     * Atomically reserve and return the next available job, or null if none.
     * The job remains in the queue (reserved) until delete()/release()/fail().
     */
    public function pop(string $queue = 'default'): ?QueuedJob;

    /**
     * Acknowledge successful processing: permanently remove the job.
     */
    public function delete(QueuedJob $job): void;

    /**
     * Return a reserved job to the queue for a later retry (increments attempts).
     */
    public function release(QueuedJob $job, int $delaySeconds = 0): void;

    /**
     * Move a reserved job to the failed-jobs store with the failure reason.
     */
    public function fail(QueuedJob $job, string $exception): void;

    /**
     * Number of jobs waiting (not currently reserved) in a queue.
     */
    public function size(string $queue = 'default'): int;

    /**
     * Clear all jobs from a queue.
     */
    public function clear(string $queue = 'default'): void;
}
