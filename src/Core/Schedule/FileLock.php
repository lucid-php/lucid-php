<?php

declare(strict_types=1);

namespace Core\Schedule;

/**
 * Filesystem lock backed by flock().
 *
 * Dependency-free and explicit. A held lock is bound to the owning process's
 * open file handle, so if a long-running scheduled task is still executing when
 * the next cron tick fires, the new process cannot acquire the lock and skips —
 * exactly the overlap protection we want. The OS releases the lock if the
 * process dies, so a crash cannot wedge the lock permanently.
 */
class FileLock implements LockInterface
{
    /** @var array<string, resource> Held lock handles keyed by lock key */
    private array $handles = [];

    public function __construct(
        private readonly string $lockPath
    ) {
        if (!is_dir($this->lockPath)) {
            mkdir($this->lockPath, 0775, true);
        }
    }

    public function acquire(string $key): bool
    {
        $file = $this->lockPath . '/' . $this->fileName($key);
        $handle = fopen($file, 'c');

        if ($handle === false) {
            return false;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }

        $this->handles[$key] = $handle;

        return true;
    }

    public function release(string $key): void
    {
        if (!isset($this->handles[$key])) {
            return;
        }

        $handle = $this->handles[$key];
        flock($handle, LOCK_UN);
        fclose($handle);
        unset($this->handles[$key]);
    }

    private function fileName(string $key): string
    {
        return preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) . '.lock';
    }
}
