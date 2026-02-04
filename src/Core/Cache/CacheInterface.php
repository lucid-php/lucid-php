<?php

declare(strict_types=1);

namespace Core\Cache;

/**
 * Cache Interface
 * 
 * Defines the contract for cache implementations following Zero Magic philosophy.
 * All operations are explicit with clear return types and no hidden behavior.
 * 
 * Philosophy:
 * - Explicit TTL (Time To Live) for every cached item
 * - Clear null returns when items don't exist or have expired
 * - Type-safe operations with strict typing
 * - No magic serialization - values stored as-is
 */
interface CacheInterface
{
    /**
     * Store a value in the cache with TTL
     * 
     * @param string $key Cache key (unique identifier)
     * @param mixed $value Value to cache (will be serialized)
     * @param int $ttl Time to live in seconds
     * @return bool True if stored successfully, false otherwise
     */
    public function set(string $key, mixed $value, int $ttl): bool;

    /**
     * Retrieve a value from the cache
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if key doesn't exist or expired
     * @return mixed The cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if a key exists and is not expired
     * 
     * @param string $key Cache key
     * @return bool True if exists and not expired
     */
    public function has(string $key): bool;

    /**
     * Delete a specific cache entry
     * 
     * @param string $key Cache key
     * @return bool True if deleted, false if didn't exist
     */
    public function delete(string $key): bool;

    /**
     * Clear all cache entries
     * 
     * @return bool True if cleared successfully
     */
    public function clear(): bool;

    /**
     * Store multiple values at once
     * 
     * @param array<string, mixed> $values Key-value pairs to cache
     * @param int $ttl Time to live in seconds
     * @return bool True if all stored successfully
     */
    public function setMultiple(array $values, int $ttl): bool;

    /**
     * Retrieve multiple values at once
     * 
     * @param array<string> $keys Cache keys to retrieve
     * @param mixed $default Default value for missing keys
     * @return array<string, mixed> Key-value pairs (missing keys use default)
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Delete multiple cache entries
     * 
     * @param array<string> $keys Cache keys to delete
     * @return bool True if all deleted successfully
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Get or set a cached value (cache-aside pattern)
     * 
     * If key exists, return cached value.
     * If not, execute callback, store result, and return it.
     * 
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int $ttl Time to live in seconds
     * @return mixed The cached or computed value
     */
    public function remember(string $key, callable $callback, int $ttl): mixed;
}
