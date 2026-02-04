<?php

declare(strict_types=1);

namespace Core\RateLimit;

/**
 * In-Memory Rate Limit Store
 * 
 * Simple in-memory storage using arrays.
 * NOT suitable for distributed systems (single-server only).
 * Resets on server restart.
 * 
 * Philosophy: Explicit, simple implementation.
 * - No external dependencies
 * - Clear, traceable logic
 * - Good enough for development and single-server production
 * 
 * For production with multiple servers, implement RedisRateLimitStore.
 */
class InMemoryRateLimitStore implements RateLimitStore
{
    /**
     * @var array<string, array{count: int, reset: int}>
     */
    private array $store = [];

    public function increment(string $key, int $window): int
    {
        $now = time();

        // If key doesn't exist or has expired, initialize it
        if (!isset($this->store[$key]) || $this->store[$key]['reset'] < $now) {
            $this->store[$key] = [
                'count' => 1,
                'reset' => $now + $window,
            ];
            return 1;
        }

        // Increment the counter
        $this->store[$key]['count']++;
        return $this->store[$key]['count'];
    }

    public function get(string $key): int
    {
        $now = time();

        // If key doesn't exist or has expired, return 0
        if (!isset($this->store[$key]) || $this->store[$key]['reset'] < $now) {
            return 0;
        }

        return $this->store[$key]['count'];
    }

    public function getResetTime(string $key): int
    {
        $now = time();

        // If key doesn't exist or has expired, return 0
        if (!isset($this->store[$key]) || $this->store[$key]['reset'] < $now) {
            return 0;
        }

        return $this->store[$key]['reset'];
    }

    public function reset(string $key): void
    {
        unset($this->store[$key]);
    }

    /**
     * Clear all rate limit data (useful for testing)
     */
    public function clear(): void
    {
        $this->store = [];
    }
}
