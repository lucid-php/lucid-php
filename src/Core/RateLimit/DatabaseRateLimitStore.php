<?php

declare(strict_types=1);

namespace Core\RateLimit;

use Core\Database\Database;

/**
 * Database-backed rate limit store.
 *
 * Unlike InMemoryRateLimitStore, counters live in a shared `rate_limits` table,
 * so limits hold across multiple PHP-FPM workers / processes and survive a
 * restart — a single-process in-memory store silently lets each worker keep its
 * own count, which defeats the limit under real concurrency.
 *
 * Each key is a fixed-window counter: the first hit in a window sets hits=1 and
 * reset_at=now+window; subsequent hits increment until the window elapses, after
 * which the next hit starts a fresh window.
 *
 * Atomicity: the increment is a single upsert (ON CONFLICT / ON DUPLICATE KEY),
 * so two concurrent requests cannot both read-then-write and lose an increment.
 * The read-back runs in the same transaction as the write.
 */
class DatabaseRateLimitStore implements RateLimitStore
{
    public function __construct(private readonly Database $db)
    {
    }

    public function increment(string $key, int $window): int
    {
        $now = time();
        $reset = $now + max(1, $window);

        return $this->db->transaction(function () use ($key, $now, $reset): int {
            $this->upsert($key, $now, $reset);

            $rows = $this->db->query(
                'SELECT hits FROM rate_limits WHERE rate_key = ?',
                [$key]
            );

            return (int) ($rows[0]['hits'] ?? 0);
        });
    }

    private function upsert(string $key, int $now, int $reset): void
    {
        // An expired window (reset_at < now) restarts the counter at 1; otherwise
        // the existing counter is incremented. Driver-specific upsert syntax,
        // mirroring the driver-branching pattern already used in Migrator.
        // Positional placeholders are used (not named) because the same value is
        // bound more than once and PDO with emulation disabled forbids reusing a
        // named placeholder.
        $sql = match ($this->db->getDriverName()) {
            'mysql' => 'INSERT INTO rate_limits (rate_key, hits, reset_at)
                        VALUES (?, 1, ?)
                        ON DUPLICATE KEY UPDATE
                            hits = IF(reset_at < ?, 1, hits + 1),
                            reset_at = IF(reset_at < ?, ?, reset_at)',
            default => 'INSERT INTO rate_limits (rate_key, hits, reset_at)
                        VALUES (?, 1, ?)
                        ON CONFLICT(rate_key) DO UPDATE SET
                            hits = CASE WHEN rate_limits.reset_at < ? THEN 1 ELSE rate_limits.hits + 1 END,
                            reset_at = CASE WHEN rate_limits.reset_at < ? THEN ? ELSE rate_limits.reset_at END',
        };

        $this->db->execute($sql, [$key, $reset, $now, $now, $reset]);
    }

    public function get(string $key): int
    {
        $rows = $this->db->query(
            'SELECT hits, reset_at FROM rate_limits WHERE rate_key = ?',
            [$key]
        );

        if ($rows === [] || (int) $rows[0]['reset_at'] < time()) {
            return 0;
        }

        return (int) $rows[0]['hits'];
    }

    public function getResetTime(string $key): int
    {
        $rows = $this->db->query(
            'SELECT reset_at FROM rate_limits WHERE rate_key = ?',
            [$key]
        );

        $resetAt = (int) ($rows[0]['reset_at'] ?? 0);

        return $resetAt < time() ? 0 : $resetAt;
    }

    public function reset(string $key): void
    {
        $this->db->execute('DELETE FROM rate_limits WHERE rate_key = ?', [$key]);
    }

    /**
     * Delete every expired window. Call periodically (e.g. from a scheduled
     * cleanup job) so the table does not grow unbounded as keys age out.
     */
    public function purgeExpired(): void
    {
        $this->db->execute('DELETE FROM rate_limits WHERE reset_at < ?', [time()]);
    }
}
