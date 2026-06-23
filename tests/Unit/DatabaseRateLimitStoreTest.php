<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Database\Database;
use Core\RateLimit\DatabaseRateLimitStore;
use PHPUnit\Framework\TestCase;

class DatabaseRateLimitStoreTest extends TestCase
{
    private Database $db;
    private DatabaseRateLimitStore $store;

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');
        $this->db->execute(
            'CREATE TABLE rate_limits (
                rate_key VARCHAR(255) PRIMARY KEY,
                hits INT NOT NULL DEFAULT 0,
                reset_at INT NOT NULL DEFAULT 0
            )'
        );
        $this->store = new DatabaseRateLimitStore($this->db);
    }

    public function test_increment_counts_within_window(): void
    {
        $this->assertSame(1, $this->store->increment('k', 60));
        $this->assertSame(2, $this->store->increment('k', 60));
        $this->assertSame(3, $this->store->increment('k', 60));
        $this->assertSame(3, $this->store->get('k'));
    }

    public function test_separate_keys_are_independent(): void
    {
        $this->store->increment('a', 60);
        $this->store->increment('a', 60);
        $this->store->increment('b', 60);

        $this->assertSame(2, $this->store->get('a'));
        $this->assertSame(1, $this->store->get('b'));
    }

    public function test_reset_clears_counter(): void
    {
        $this->store->increment('k', 60);
        $this->store->reset('k');

        $this->assertSame(0, $this->store->get('k'));
    }

    public function test_reset_time_is_in_the_future_within_window(): void
    {
        $this->store->increment('k', 60);

        $this->assertGreaterThanOrEqual(time(), $this->store->getResetTime('k'));
    }

    public function test_expired_window_restarts_counter(): void
    {
        $this->store->increment('k', 60);
        // Age the window into the past.
        $this->db->execute(
            'UPDATE rate_limits SET reset_at = ? WHERE rate_key = ?',
            [time() - 10, 'k']
        );

        // Expired reads as 0...
        $this->assertSame(0, $this->store->get('k'));
        $this->assertSame(0, $this->store->getResetTime('k'));
        // ...and the next hit starts a fresh window at 1.
        $this->assertSame(1, $this->store->increment('k', 60));
    }

    public function test_purge_expired_removes_only_stale_rows(): void
    {
        $this->store->increment('fresh', 60);
        $this->store->increment('stale', 60);
        $this->db->execute(
            'UPDATE rate_limits SET reset_at = ? WHERE rate_key = ?',
            [time() - 1, 'stale']
        );

        $this->store->purgeExpired();

        $remaining = $this->db->query('SELECT rate_key FROM rate_limits');
        $this->assertSame([['rate_key' => 'fresh']], $remaining);
    }
}
