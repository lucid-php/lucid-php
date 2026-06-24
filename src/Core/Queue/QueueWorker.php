<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Attribute\Retry;
use Core\Container;
use ReflectionClass;
use Throwable;

/**
 * Queue Worker
 *
 * Processes jobs from the queue.
 *
 * Philosophy:
 * - Explicit job handling — you see the handle() method call
 * - Jobs resolved from container (dependency injection)
 * - Explicit error handling — no silent failures, but no lost jobs either:
 *   a failed job is retried (if it opts in via #[Retry]) or moved to the
 *   failed-jobs store, never dropped.
 */
class QueueWorker
{
    public function __construct(
        private readonly QueueInterface $queue,
        private readonly Container $container
    ) {
    }

    /**
     * Execute a job's handle() method, resolving its dependencies from the
     * container. Exceptions propagate to the caller — this is the raw run with
     * no queue acknowledgement (used directly by the synchronous queue).
     */
    public function runJob(object $job): void
    {
        if (!method_exists($job, 'handle')) {
            throw new \RuntimeException(
                'Job class ' . get_class($job) . ' must implement a handle() method'
            );
        }

        $reflection = new ReflectionClass($job);
        $method = $reflection->getMethod('handle');

        $args = [];
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof \ReflectionNamedType) {
                continue;
            }

            $typeName = $type->getName();

            if ($this->container->has($typeName)) {
                $args[] = $this->container->get($typeName);
            }
        }

        $job->handle(...$args);
    }

    /**
     * Process a single reserved job, then acknowledge it: delete on success,
     * release for retry (if the job opts in via #[Retry]) or move to the
     * failed-jobs store when retries are exhausted.
     */
    public function processJob(QueuedJob $queuedJob): void
    {
        try {
            $this->runJob($queuedJob->job);
            $this->queue->delete($queuedJob);
        } catch (Throwable $e) {
            error_log(sprintf('Job failed: %s - %s', $queuedJob->job::class, $e->getMessage()));

            $retry = $this->retryPolicy($queuedJob->job);

            // $queuedJob->attempts is the count of prior attempts; this run is
            // attempt #(attempts + 1).
            if ($queuedJob->attempts + 1 < $retry->times) {
                $this->queue->release($queuedJob, $retry->backoff);
            } else {
                $this->queue->fail($queuedJob, (string) $e);
            }
        }
    }

    /**
     * Resolve the retry policy from the job's #[Retry] attribute.
     * Default (no attribute) is a single attempt — no hidden retries.
     */
    private function retryPolicy(object $job): Retry
    {
        $attributes = (new ReflectionClass($job))->getAttributes(Retry::class);

        return $attributes === [] ? new Retry(times: 1, backoff: 0) : $attributes[0]->newInstance();
    }

    /**
     * Work the queue continuously.
     *
     * A single job failure never stops the worker — failures are handled by
     * processJob(); transport errors (pop/unserialize) are caught here.
     *
     * @param string $queue Queue name to process
     * @param int    $sleep Seconds to sleep when the queue is empty
     */
    public function work(string $queue = 'default', int $sleep = 3): never
    {
        while (true) {
            try {
                $queuedJob = $this->queue->pop($queue);

                if ($queuedJob === null) {
                    sleep($sleep);
                    continue;
                }

                $this->processJob($queuedJob);
            } catch (Throwable $e) {
                error_log('Queue worker error: ' . $e->getMessage());
                sleep($sleep);
            }
        }
    }

    /**
     * Process one job and exit.
     * Useful for running the queue worker in a scheduled cron.
     */
    public function workOnce(string $queue = 'default'): void
    {
        $queuedJob = $this->queue->pop($queue);

        if ($queuedJob !== null) {
            $this->processJob($queuedJob);
        }
    }
}
