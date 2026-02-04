<?php

declare(strict_types=1);

namespace Core\Cache;

/**
 * In-Memory Array Cache Implementation
 * 
 * Stores cached values in PHP memory (not persistent).
 * Perfect for testing and temporary caching within a request lifecycle.
 * 
 * Features:
 * - Lightning fast (no I/O)
 * - No disk space usage
 * - Automatic cleanup on script end
 * - Perfect for unit tests
 * - Request introspection methods
 * 
 * Use Cases:
 * - Unit testing without filesystem
 * - Temporary caching within single request
 * - Development and debugging
 * 
 * Philosophy Compliance:
 * - Zero Magic: Simple array operations, explicit
 * - Strict Typing: All parameters and returns typed
 * - Traceable: Can inspect internal state
 */
class ArrayCache implements CacheInterface
{
    /**
     * @var array<string, array{expires_at: int, value: mixed}>
     */
    private array $storage = [];

    /**
     * @var array<string> Track all keys that have been set (for testing)
     */
    private array $setKeys = [];

    public function set(string $key, mixed $value, int $ttl): bool
    {
        if ($ttl <= 0) {
            throw CacheException::invalidTtl($ttl);
        }

        $this->storage[$key] = [
            'expires_at' => time() + $ttl,
            'value' => $value,
        ];

        $this->setKeys[] = $key;

        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->storage[$key])) {
            return $default;
        }

        $data = $this->storage[$key];

        // Check expiration
        if (time() > $data['expires_at']) {
            unset($this->storage[$key]);
            return $default;
        }

        return $data['value'];
    }

    public function has(string $key): bool
    {
        if (!isset($this->storage[$key])) {
            return false;
        }

        $data = $this->storage[$key];

        // Check expiration
        if (time() > $data['expires_at']) {
            unset($this->storage[$key]);
            return false;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        if (!isset($this->storage[$key])) {
            return false;
        }

        unset($this->storage[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];
        $this->setKeys = [];
        return true;
    }

    public function setMultiple(array $values, int $ttl): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function getMultiple(array $keys, mixed $default = null): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function remember(string $key, callable $callback, int $ttl): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Get all stored keys (useful for testing)
     * 
     * @return array<string>
     */
    public function getAllKeys(): array
    {
        return array_keys($this->storage);
    }

    /**
     * Get all keys that have been set (even expired ones)
     * Useful for testing cache behavior
     * 
     * @return array<string>
     */
    public function getSetKeys(): array
    {
        return $this->setKeys;
    }

    /**
     * Get the number of items currently in cache (non-expired)
     */
    public function count(): int
    {
        // Clean expired entries first
        foreach ($this->storage as $key => $data) {
            if (time() > $data['expires_at']) {
                unset($this->storage[$key]);
            }
        }

        return count($this->storage);
    }

    /**
     * Assert that a specific key was set (useful for testing)
     */
    public function assertSet(string $key): bool
    {
        return in_array($key, $this->setKeys, true);
    }

    /**
     * Reset tracking state (useful for testing)
     */
    public function reset(): void
    {
        $this->clear();
    }
}
