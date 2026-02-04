<?php

declare(strict_types=1);

namespace Core\Cache;

/**
 * File-based Cache Implementation
 * 
 * Stores cached values as serialized files in a directory structure.
 * Production-ready implementation with proper file locking and atomic writes.
 * 
 * Features:
 * - Persistent storage (survives restarts)
 * - TTL support with expiration checking
 * - Atomic writes to prevent corruption
 * - Automatic cleanup of expired entries
 * - Safe serialization/unserialization
 * 
 * Philosophy Compliance:
 * - Zero Magic: Explicit file paths, clear serialization
 * - Strict Typing: All parameters and returns typed
 * - Traceable: File operations can be inspected on disk
 */
readonly class FileCache implements CacheInterface
{
    /**
     * @param string $path Directory path for cache files
     */
    public function __construct(
        private string $path
    ) {
        $this->ensureDirectoryExists();
    }

    public function set(string $key, mixed $value, int $ttl): bool
    {
        if ($ttl <= 0) {
            throw CacheException::invalidTtl($ttl);
        }

        $filePath = $this->getFilePath($key);
        $expiresAt = time() + $ttl;

        $data = [
            'expires_at' => $expiresAt,
            'value' => $value,
        ];

        try {
            $serialized = serialize($data);
        } catch (\Exception $e) {
            throw CacheException::serializationFailed($key, $e->getMessage());
        }

        // Write atomically using temp file + rename
        $tempPath = $filePath . '.tmp';
        $written = file_put_contents($tempPath, $serialized, LOCK_EX);

        if ($written === false) {
            throw CacheException::writeFailed($key, 'Could not write to temporary file');
        }

        if (!rename($tempPath, $filePath)) {
            @unlink($tempPath);
            throw CacheException::writeFailed($key, 'Could not rename temporary file');
        }

        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return $default;
        }

        $contents = @file_get_contents($filePath);

        if ($contents === false) {
            return $default;
        }

        try {
            $data = unserialize($contents);
        } catch (\Exception) {
            // Corrupted cache file, delete it
            @unlink($filePath);
            return $default;
        }

        // Check expiration
        if (!isset($data['expires_at']) || !isset($data['value'])) {
            @unlink($filePath);
            return $default;
        }

        if (time() > $data['expires_at']) {
            @unlink($filePath);
            return $default;
        }

        return $data['value'];
    }

    public function has(string $key): bool
    {
        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return false;
        }

        $contents = @file_get_contents($filePath);

        if ($contents === false) {
            return false;
        }

        try {
            $data = unserialize($contents);
        } catch (\Exception) {
            @unlink($filePath);
            return false;
        }

        if (!isset($data['expires_at'])) {
            @unlink($filePath);
            return false;
        }

        if (time() > $data['expires_at']) {
            @unlink($filePath);
            return false;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return false;
        }

        return @unlink($filePath);
    }

    public function clear(): bool
    {
        $files = glob($this->path . '/*.cache');

        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

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
     * Get the file path for a cache key
     */
    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->path . '/' . $hash . '.cache';
    }

    /**
     * Ensure cache directory exists with proper permissions
     */
    private function ensureDirectoryExists(): void
    {
        if (is_dir($this->path)) {
            return;
        }

        if (!mkdir($this->path, 0755, true)) {
            throw CacheException::directoryCreationFailed(
                $this->path,
                'Could not create directory'
            );
        }
    }
}
