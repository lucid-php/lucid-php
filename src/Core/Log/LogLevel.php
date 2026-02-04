<?php

declare(strict_types=1);

namespace Core\Log;

/**
 * Log Level Enum
 * 
 * Philosophy: Explicit log levels, no magic strings.
 * PSR-3 compatible levels as typed enum.
 */
enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
    case ALERT = 'alert';
    case EMERGENCY = 'emergency';

    /**
     * Get numeric severity (for filtering)
     * Lower number = more severe
     */
    public function severity(): int
    {
        return match ($this) {
            self::EMERGENCY => 0,
            self::ALERT => 1,
            self::CRITICAL => 2,
            self::ERROR => 3,
            self::WARNING => 4,
            self::NOTICE => 5,
            self::INFO => 6,
            self::DEBUG => 7,
        };
    }

    /**
     * Check if this level should be logged based on minimum level
     */
    public function shouldLog(LogLevel $minimumLevel): bool
    {
        return $this->severity() <= $minimumLevel->severity();
    }
}
