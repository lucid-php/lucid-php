<?php

declare(strict_types=1);

namespace Core\Log;

/**
 * Logger Interface
 * 
 * Philosophy: Explicit logging - every log call is visible in code.
 * No auto-logging, no hidden telemetry, no magic.
 * 
 * PSR-3 compatible interface with typed parameters.
 */
interface LoggerInterface
{
    /**
     * Log a message at the specified level
     * 
     * @param LogLevel $level Log level (typed enum)
     * @param string $message Log message (can contain {placeholders})
     * @param array<string, mixed> $context Additional context data
     */
    public function log(LogLevel $level, string $message, array $context = []): void;

    /**
     * System is unusable
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Action must be taken immediately
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Critical conditions
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Runtime errors that do not require immediate action
     */
    public function error(string $message, array $context = []): void;

    /**
     * Exceptional occurrences that are not errors
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Normal but significant events
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Interesting events
     */
    public function info(string $message, array $context = []): void;

    /**
     * Detailed debug information
     */
    public function debug(string $message, array $context = []): void;
}
