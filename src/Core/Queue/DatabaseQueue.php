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
 * - No hidden retry logic - must be explicit
 */
class DatabaseQueue implements QueueInterface
{
    /**
     * @param array<class-string> $allowedJobClasses Whitelist of job classes allowed to be unserialized
     */
    public function __construct(
        private final Database $db,
        private readonly array $allowedJobClasses = [],
    ) {}

    public function push(object $job, string $queue = 'default'): void
    {
        $id = bin2hex(random_bytes(16));
        
        $this->db->execute(
            "INSERT INTO jobs (id, queue, payload, attempts, available_at, created_at) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $id,
                $queue,
                serialize($job),
                0,
                time(),
                time(),
            ]
        );
    }

    public function pop(string $queue = 'default'): ?QueuedJob
    {
        // Get next available job
        $rows = $this->db->query(
            "SELECT * FROM jobs 
             WHERE queue = ? AND available_at <= ? 
             ORDER BY created_at ASC 
             LIMIT 1",
            [$queue, time()]
        );

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];

        // Delete job from queue
        $this->db->execute(
            "DELETE FROM jobs WHERE id = ?",
            [$row['id']]
        );

        // Unserialize job payload
        // SECURITY: Only allow explicitly configured job classes to prevent object injection
        // Configure allowed classes when instantiating DatabaseQueue in your container
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

    public function size(string $queue = 'default'): int
    {
        $rows = $this->db->query(
            "SELECT COUNT(*) as count FROM jobs WHERE queue = ?",
            [$queue]
        );

        return (int) $rows[0]['count'];
    }

    public function clear(string $queue = 'default'): void
    {
        $this->db->execute(
            "DELETE FROM jobs WHERE queue = ?",
            [$queue]
        );
    }
}
