<?php

declare(strict_types=1);

namespace Core\RateLimit;

/**
 * Rate Limit Store Interface
 * 
 * Abstraction for storing rate limit counters.
 * Allows different backends (in-memory, Redis, etc.)
 * 
 * Philosophy: Explicit interfaces, no magic.
 */
interface RateLimitStore
{
    /**
     * Increment the counter for a key
     * 
     * @param string $key Unique identifier (e.g., "ip:192.168.1.1:route:/api/users")
     * @param int $window Time window in seconds
     * @return int Current count after increment
     */
    public function increment(string $key, int $window): int;

    /**
     * Get the current count for a key
     * 
     * @param string $key Unique identifier
     * @return int Current count (0 if not found or expired)
     */
    public function get(string $key): int;

    /**
     * Get the time when the key expires (resets)
     * 
     * @param string $key Unique identifier
     * @return int Unix timestamp when the key resets (0 if not found)
     */
    public function getResetTime(string $key): int;

    /**
     * Reset/delete a key
     * 
     * @param string $key Unique identifier
     */
    public function reset(string $key): void;
}
