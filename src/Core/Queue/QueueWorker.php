<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Container;
use Throwable;

/**
 * Queue Worker
 * 
 * Processes jobs from the queue.
 * 
 * Philosophy:
 * - Explicit job handling - you see the handle() method call
 * - Jobs resolved from container (dependency injection)
 * - Explicit error handling - no silent failures
 */
class QueueWorker
{
    public function __construct(
        private final QueueInterface $queue,
        private final Container $container
    ) {}

    /**
     * Process a single job from the queue
     */
    public function processJob(QueuedJob $queuedJob): void
    {
        $job = $queuedJob->job;
        
        // Validate that job has a handle method
        if (!method_exists($job, 'handle')) {
            throw new \RuntimeException(
                "Job class " . get_class($job) . " must implement a handle() method"
            );
        }
        
        try {
            // Resolve dependencies for job's handle method
            $reflection = new \ReflectionClass($job);
            $method = $reflection->getMethod('handle');
            
            $args = [];
            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                
                if (!$type instanceof \ReflectionNamedType) {
                    continue;
                }
                
                $typeName = $type->getName();
                
                // Resolve from container
                if ($this->container->has($typeName)) {
                    $args[] = $this->container->get($typeName);
                }
            }
            
            // Execute job
            $job->handle(...$args);
            
        } catch (Throwable $e) {
            // Log error but don't stop worker
            error_log(sprintf(
                "Job failed: %s - %s",
                $job::class,
                $e->getMessage()
            ));
            
            throw $e;
        }
    }

    /**
     * Work the queue continuously
     * 
     * @param string $queue Queue name to process
     * @param int $sleep Seconds to sleep when queue is empty
     */
    public function work(string $queue = 'default', int $sleep = 3): never
    {
        while (true) {
            $queuedJob = $this->queue->pop($queue);
            
            if ($queuedJob === null) {
                // Queue is empty, sleep and try again
                sleep($sleep);
                continue;
            }
            
            $this->processJob($queuedJob);
        }
    }

    /**
     * Process one job and exit
     * Useful for running queue worker in scheduled cron
     */
    public function workOnce(string $queue = 'default'): void
    {
        $queuedJob = $this->queue->pop($queue);
        
        if ($queuedJob !== null) {
            $this->processJob($queuedJob);
        }
    }
}
