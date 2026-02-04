<?php

declare(strict_types=1);

namespace Core\Session;

/**
 * Native PHP Session Implementation
 * 
 * Philosophy: Zero Magic, Explicit Over Convenient
 * - Wraps native PHP session functions with explicit API
 * - No auto-start (must call start() explicitly)
 * - No array access magic (use get/set methods)
 * - Strict typing everywhere
 */
class Session implements SessionInterface
{
    private bool $started = false;

    public function __construct(
        private readonly array $options = []
    ) {}

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        if (!empty($this->options)) {
            session_start($this->options);
        } else {
            session_start();
        }

        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION;
    }

    public function clear(): void
    {
        $this->ensureStarted();
        $_SESSION = [];
    }

    public function destroy(): void
    {
        $this->ensureStarted();
        
        $_SESSION = [];
        
        if (session_id() !== '') {
            session_destroy();
        }
        
        $this->started = false;
    }

    public function regenerate(bool $deleteOldSession = true): void
    {
        $this->ensureStarted();
        session_regenerate_id($deleteOldSession);
    }

    public function getId(): string
    {
        $this->ensureStarted();
        return session_id();
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Ensure session is started before operations
     * 
     * @throws \RuntimeException
     */
    private function ensureStarted(): void
    {
        if (!$this->started) {
            throw new \RuntimeException(
                'Session not started. Call start() first.'
            );
        }
    }
}
