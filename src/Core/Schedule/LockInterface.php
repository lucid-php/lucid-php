<?php

declare(strict_types=1);

namespace Core\Schedule;

/**
 * A mutually-exclusive lock used to prevent a scheduled task from overlapping
 * with a still-running instance of itself.
 */
interface LockInterface
{
    /**
     * Attempt to acquire the lock for $key without blocking.
     * Returns true if acquired, false if it is already held.
     */
    public function acquire(string $key): bool;

    /**
     * Release a previously acquired lock for $key.
     */
    public function release(string $key): void;
}
