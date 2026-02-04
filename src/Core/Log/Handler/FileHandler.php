<?php

declare(strict_types=1);

namespace Core\Log\Handler;

use Core\Log\LogEntry;
use Core\Log\LogHandlerInterface;

/**
 * File Log Handler
 * 
 * Philosophy: Explicit file path, explicit format.
 * No auto-rotation, no magic - just write to file.
 */
class FileHandler implements LogHandlerInterface
{
    /**
     * @param string $filePath Absolute path to log file
     * @param bool $json Write as JSON (true) or plain text (false)
     */
    public function __construct(
        private readonly string $filePath,
        private readonly bool $json = true
    ) {
        // Ensure directory exists
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    public function handle(LogEntry $entry): void
    {
        $line = $this->json 
            ? $entry->toJson() 
            : $entry->toString();

        // Append to file with newline
        file_put_contents(
            $this->filePath,
            $line . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
