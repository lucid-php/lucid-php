<?php

declare(strict_types=1);

namespace Core\Log;

/**
 * Log Handler Interface
 * 
 * Philosophy: Explicit log destinations.
 * Each handler decides how/where to write logs.
 */
interface LogHandlerInterface
{
    /**
     * Handle a log entry
     */
    public function handle(LogEntry $entry): void;
}
