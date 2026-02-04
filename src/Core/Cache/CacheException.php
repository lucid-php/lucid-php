<?php

declare(strict_types=1);

namespace Core\Cache;

use Exception;

/**
 * Cache Exception
 * 
 * Thrown when cache operations fail.
 * Provides static factory methods for common error scenarios.
 */
class CacheException extends Exception
{
    /**
     * Create exception for write failures
     */
    public static function writeFailed(string $key, string $reason): self
    {
        return new self("Failed to write cache key [{$key}]: {$reason}");
    }

    /**
     * Create exception for read failures
     */
    public static function readFailed(string $key, string $reason): self
    {
        return new self("Failed to read cache key [{$key}]: {$reason}");
    }

    /**
     * Create exception for serialization failures
     */
    public static function serializationFailed(string $key, string $reason): self
    {
        return new self("Failed to serialize value for key [{$key}]: {$reason}");
    }

    /**
     * Create exception for directory creation failures
     */
    public static function directoryCreationFailed(string $path, string $reason): self
    {
        return new self("Failed to create cache directory [{$path}]: {$reason}");
    }

    /**
     * Create exception for invalid TTL
     */
    public static function invalidTtl(int $ttl): self
    {
        return new self("Invalid TTL value [{$ttl}]. Must be positive integer.");
    }
}
