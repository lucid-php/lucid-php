<?php

declare(strict_types=1);

namespace Core\Log;

/**
 * Logger Implementation
 * 
 * Philosophy: Explicit, traceable logging with typed context.
 * - No magic context enrichment
 * - No auto-discovery of log destinations
 * - Explicit handler registration
 * - Structured context (arrays, not mixed)
 */
class Logger implements LoggerInterface
{
    /** @var array<LogHandlerInterface> */
    private array $handlers = [];
    
    private readonly LogLevel $minimumLevel;

    /**
     * @param LogLevel $minimumLevel Only log messages at this level or higher severity
     * @param array<LogHandlerInterface> $handlers Log handlers (explicit registration)
     */
    public function __construct(
        LogLevel $minimumLevel = LogLevel::DEBUG,
        array $handlers = []
    ) {
        $this->minimumLevel = $minimumLevel;
        $this->handlers = $handlers;
    }

    /**
     * Add a log handler
     */
    public function addHandler(LogHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function log(LogLevel $level, string $message, array $context = []): void
    {
        // Check if this level should be logged
        if (!$level->shouldLog($this->minimumLevel)) {
            return;
        }

        // Interpolate context into message
        $message = $this->interpolate($message, $context);

        // Create log entry
        $entry = new LogEntry(
            level: $level,
            message: $message,
            context: $context,
            timestamp: new \DateTimeImmutable()
        );

        // Pass to all handlers
        foreach ($this->handlers as $handler) {
            $handler->handle($entry);
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Interpolate context values into message placeholders
     * Example: "User {user_id} logged in" with ['user_id' => 42]
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];
        
        foreach ($context as $key => $value) {
            // Only replace scalar values
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replacements['{' . $key . '}'] = $value;
            }
        }

        return strtr($message, $replacements);
    }
}
