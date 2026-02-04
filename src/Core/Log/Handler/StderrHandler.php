<?php

declare(strict_types=1);

namespace Core\Log\Handler;

use Core\Log\LogEntry;
use Core\Log\LogHandlerInterface;

/**
 * Stderr Log Handler
 * 
 * Philosophy: Explicit stderr output for containers/CLI.
 * Writes directly to STDERR stream.
 */
class StderrHandler implements LogHandlerInterface
{
    /**
     * @param bool $json Write as JSON (true) or plain text (false)
     */
    public function __construct(
        private readonly bool $json = true
    ) {}

    public function handle(LogEntry $entry): void
    {
        $line = $this->json 
            ? $entry->toJson() 
            : $entry->toString();

        // Write to STDERR
        fwrite(STDERR, $line . PHP_EOL);
    }
}
