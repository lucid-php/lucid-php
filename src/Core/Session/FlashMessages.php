<?php

declare(strict_types=1);

namespace Core\Session;

/**
 * Flash Messages
 * 
 * One-time messages that survive a redirect and are then cleared.
 * Common use case: "User created successfully" after form submission.
 * 
 * Philosophy: Explicit Over Convenient
 * - No magic levels (use explicit success/error/info methods)
 * - Messages are typed arrays, not mixed
 * - Clear separation between setting and getting
 */
class FlashMessages
{
    private const KEY = '_flash_messages';

    public function __construct(
        private readonly SessionInterface $session
    ) {}

    /**
     * Add a success message
     */
    public function success(string $message): void
    {
        $this->add('success', $message);
    }

    /**
     * Add an error message
     */
    public function error(string $message): void
    {
        $this->add('error', $message);
    }

    /**
     * Add an info message
     */
    public function info(string $message): void
    {
        $this->add('info', $message);
    }

    /**
     * Add a warning message
     */
    public function warning(string $message): void
    {
        $this->add('warning', $message);
    }

    /**
     * Get all messages of a specific type
     * Messages are removed after retrieval
     * 
     * @return string[]
     */
    public function get(string $type): array
    {
        $messages = $this->session->get(self::KEY, []);
        $result = $messages[$type] ?? [];
        
        // Remove retrieved messages
        if (isset($messages[$type])) {
            unset($messages[$type]);
            $this->session->set(self::KEY, $messages);
        }

        return $result;
    }

    /**
     * Get all messages (all types)
     * Messages are removed after retrieval
     * 
     * @return array<string, string[]>
     */
    public function getAll(): array
    {
        $messages = $this->session->get(self::KEY, []);
        $this->session->remove(self::KEY);
        return $messages;
    }

    /**
     * Check if there are any messages of a specific type
     */
    public function has(string $type): bool
    {
        $messages = $this->session->get(self::KEY, []);
        return !empty($messages[$type]);
    }

    /**
     * Check if there are any messages at all
     */
    public function hasAny(): bool
    {
        $messages = $this->session->get(self::KEY, []);
        return !empty($messages);
    }

    /**
     * Add a message to the flash storage
     */
    private function add(string $type, string $message): void
    {
        $messages = $this->session->get(self::KEY, []);
        
        if (!isset($messages[$type])) {
            $messages[$type] = [];
        }
        
        $messages[$type][] = $message;
        $this->session->set(self::KEY, $messages);
    }
}
