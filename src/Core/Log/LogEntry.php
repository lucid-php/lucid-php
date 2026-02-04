<?php

declare(strict_types=1);

namespace Core\Log;

/**
 * Log Entry Value Object
 * 
 * Philosophy: Immutable value object with all log data.
 * No magic properties, everything explicit.
 */
final readonly class LogEntry
{
    /**
     * @param LogLevel $level
     * @param string $message Interpolated message
     * @param array<string, mixed> $context Structured context data
     * @param \DateTimeImmutable $timestamp
     */
    public function __construct(
        public LogLevel $level,
        public string $message,
        public array $context,
        public \DateTimeImmutable $timestamp
    ) {}

    /**
     * Format as JSON for structured logging
     */
    public function toJson(): string
    {
        return json_encode([
            'timestamp' => $this->timestamp->format(\DateTimeInterface::RFC3339_EXTENDED),
            'level' => $this->level->value,
            'message' => $this->message,
            'context' => $this->context,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Format as plain text for human-readable logs
     */
    public function toString(): string
    {
        $timestamp = $this->timestamp->format('Y-m-d H:i:s.u');
        $level = strtoupper($this->level->value);
        
        $contextString = '';
        if (!empty($this->context)) {
            $contextString = ' ' . json_encode($this->context, JSON_UNESCAPED_SLASHES);
        }

        return "[$timestamp] $level: {$this->message}$contextString";
    }
}
