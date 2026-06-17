<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Database\Database;

/**
 * Database-backed Queue Implementation
 *
 * Philosophy:
 * - Explicit SQL queries, no ORM magic
 * - Jobs stored as serialized PHP objects (explicit serialization)
 * - No hidden retry logic — retries are opt-in via #[Retry] on the job
 *
 * Reserve-then-acknowledge: pop() atomically claims a job (it stays in the
 * table, marked reserved) and the worker later delete()s, release()s, or
 * fail()s it. A crash between pop() and acknowledgement therefore never loses
 * the job — it remains reserved and can be recovered.
 */
class DatabaseQueue implements QueueInterface
{
    /**
     * @param array<class-string> $allowedJobClasses Whitelist of job classes allowed to be unserialized
     */
    public function __construct(
        private readonly Database $db,
        private readonly array $allowedJobClasses = [],
    ) {}

    public function push(object $job, string $queue = 'default', int $delaySeconds = 0): void
    {
        $id = bin2hex(random_bytes(16));
        $now = time();

        $this->db->execute(
            "INSERT INTO jobs (id, queue, payload, attempts, available_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $id,
                $queue,
                serialize($job),
                0,
                $now + max(0, $delaySeconds),
                $now,
            ]
        );
    }

    public function pop(string $queue = 'default'): ?QueuedJob
    {
        $token = bin2hex(random_bytes(16));
        $now = time();

        // Atomically claim the oldest available, unreserved job in a single
        // statement. The nested subquery keeps this valid on both SQLite and
        // MySQL (MySQL forbids referencing the UPDATE target directly in a
        // subquery, so it is wrapped one level deeper).
        $this->db->execute(
            "UPDATE jobs
             SET reserved_at = ?, reservation_token = ?
             WHERE id = (
                 SELECT id FROM (
                     SELECT id FROM jobs
                     WHERE queue = ? AND available_at <= ? AND reserved_at IS NULL
                     ORDER BY created_at ASC
                     LIMIT 1
                 ) AS next_job
             )",
            [$now, $token, $queue, $now]
        );

        // Fetch the row we (and only we) just claimed via the unique token.
        $rows = $this->db->query(
            "SELECT * FROM jobs WHERE reservation_token = ? LIMIT 1",
            [$token]
        );

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];

        // SECURITY: only explicitly allowed job classes may be unserialized.
        try {
            $job = unserialize($row['payload'], ['allowed_classes' => $this->allowedJobClasses]);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to unserialize job: " . $e->getMessage());
        }

        return new QueuedJob(
            id: $row['id'],
            job: $job,
            queue: $row['queue'],
            attempts: (int) $row['attempts'],
            availableAt: (int) $row['available_at'],
        );
    }

    public function delete(QueuedJob $job): void
    {
        $this->db->execute("DELETE FROM jobs WHERE id = ?", [$job->id]);
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): void
    {
        $this->db->execute(
            "UPDATE jobs
             SET reserved_at = NULL, reservation_token = NULL,
                 available_at = ?, attempts = attempts + 1
             WHERE id = ?",
            [time() + max(0, $delaySeconds), $job->id]
        );
    }

    public function fail(QueuedJob $job, string $exception): void
    {
        // Record the failure and remove from the live queue, atomically — so a
        // failed job is never lost and never left half-processed.
        $this->db->transaction(function () use ($job, $exception): void {
            $this->db->execute(
                "INSERT INTO failed_jobs (id, queue, payload, attempts, exception, failed_at)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $job->id,
                    $job->queue,
                    serialize($job->job),
                    $job->attempts,
                    $exception,
                    time(),
                ]
            );

            $this->db->execute("DELETE FROM jobs WHERE id = ?", [$job->id]);
        });
    }

    /**
     * List failed jobs (raw rows, no payload deserialization).
     *
     * @return array<int, array<string, mixed>>
     */
    public function failedJobs(): array
    {
        return $this->db->query(
            "SELECT id, queue, attempts, exception, failed_at
             FROM failed_jobs ORDER BY failed_at DESC"
        );
    }

    /**
     * Re-enqueue a failed job by id. Returns false if not found.
     * Moves the stored payload back without deserializing it.
     */
    public function retryFailed(string $id): bool
    {
        $rows = $this->db->query("SELECT * FROM failed_jobs WHERE id = ? LIMIT 1", [$id]);

        if (empty($rows)) {
            return false;
        }

        $row = $rows[0];
        $now = time();

        $this->db->transaction(function () use ($row, $now): void {
            $this->db->execute(
                "INSERT INTO jobs (id, queue, payload, attempts, available_at, created_at)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$row['id'], $row['queue'], $row['payload'], 0, $now, $now]
            );
            $this->db->execute("DELETE FROM failed_jobs WHERE id = ?", [$row['id']]);
        });

        return true;
    }

    /**
     * Re-enqueue every failed job. Returns the number re-enqueued.
     */
    public function retryAllFailed(): int
    {
        $rows = $this->db->query("SELECT id FROM failed_jobs");
        $count = 0;

        foreach ($rows as $row) {
            if ($this->retryFailed($row['id'])) {
                $count++;
            }
        }

        return $count;
    }

    public function size(string $queue = 'default'): int
    {
        $rows = $this->db->query(
            "SELECT COUNT(*) as count FROM jobs WHERE queue = ? AND reserved_at IS NULL",
            [$queue]
        );

        return (int) $rows[0]['count'];
    }

    public function clear(string $queue = 'default'): void
    {
        $this->db->execute("DELETE FROM jobs WHERE queue = ?", [$queue]);
    }
}
