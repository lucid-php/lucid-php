<?php

declare(strict_types=1);

namespace Core\Session;

/**
 * Session Management Interface
 * 
 * Philosophy: Explicit Over Convenient
 * - No magic __get() or array access
 * - Every method is typed and explicit
 * - No hidden behaviors or auto-start
 */
interface SessionInterface
{
    /**
     * Start the session
     * Must be called before any session operations
     */
    public function start(): void;

    /**
     * Get a value from the session
     * 
     * @template T
     * @param string $key
     * @param T $default
     * @return T|mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a value in the session
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a key exists in the session
     */
    public function has(string $key): bool;

    /**
     * Remove a key from the session
     */
    public function remove(string $key): void;

    /**
     * Get all session data
     * 
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Clear all session data
     */
    public function clear(): void;

    /**
     * Destroy the session
     * Removes all data and invalidates the session ID
     */
    public function destroy(): void;

    /**
     * Regenerate the session ID
     * Important for security after authentication
     */
    public function regenerate(bool $deleteOldSession = true): void;

    /**
     * Get the current session ID
     */
    public function getId(): string;

    /**
     * Check if the session has been started
     */
    public function isStarted(): bool;
}
