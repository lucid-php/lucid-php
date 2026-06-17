<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\Retry;
use Core\Container;
use Core\Database\Database;
use Core\Queue\DatabaseQueue;
use Core\Queue\QueueWorker;
use PHPUnit\Framework\TestCase;

class QueueRobustnessTest extends TestCase
{
    private Database $db;
    private DatabaseQueue $queue;
    private QueueWorker $worker;

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');
        $this->db->execute("
            CREATE TABLE jobs (
                id TEXT PRIMARY KEY, queue TEXT NOT NULL DEFAULT 'default', payload TEXT NOT NULL,
                attempts INTEGER NOT NULL DEFAULT 0, available_at INTEGER NOT NULL, created_at INTEGER NOT NULL,
                reserved_at INTEGER DEFAULT NULL, reservation_token TEXT DEFAULT NULL
            )
        ");
        $this->db->execute("
            CREATE TABLE failed_jobs (
                id TEXT PRIMARY KEY, queue TEXT NOT NULL DEFAULT 'default', payload TEXT NOT NULL,
                attempts INTEGER NOT NULL DEFAULT 0, exception TEXT NOT NULL, failed_at INTEGER NOT NULL
            )
        ");

        $allowed = [FailingJob::class, RetryableJob::class];
        $this->queue = new DatabaseQueue($this->db, $allowed);

        $container = new Container();
        $container->set(Database::class, $this->db);
        $this->worker = new QueueWorker($this->queue, $container);
    }

    public function testFailingJobIsMovedToFailedJobsNotLost(): void
    {
        $this->queue->push(new FailingJob());

        $queuedJob = $this->queue->pop();
        $this->assertNotNull($queuedJob);

        // No #[Retry] => single attempt then fail.
        $this->worker->processJob($queuedJob);

        $this->assertSame(0, $this->jobCount('jobs'), 'job removed from live queue');
        $this->assertSame(1, $this->jobCount('failed_jobs'), 'job preserved in failed_jobs (not lost)');

        $failed = $this->db->query("SELECT * FROM failed_jobs LIMIT 1")[0];
        $this->assertStringContainsString('boom', $failed['exception']);
    }

    public function testRetryableJobIsReleasedWithBackoffThenFails(): void
    {
        $this->queue->push(new RetryableJob()); // #[Retry(times: 2)]

        // Attempt 1: released for retry, NOT failed yet.
        $job1 = $this->queue->pop();
        $this->worker->processJob($job1);
        $this->assertSame(0, $this->jobCount('failed_jobs'), 'not failed after first attempt');
        $this->assertSame(1, $this->jobCount('jobs'), 'still in queue for retry');

        $row = $this->db->query("SELECT * FROM jobs LIMIT 1")[0];
        $this->assertSame(1, (int) $row['attempts'], 'attempts incremented on release');
        $this->assertNull($row['reserved_at'], 'released job is unreserved');

        // Make the retry immediately available, then attempt 2 (final) -> failed.
        $this->db->execute("UPDATE jobs SET available_at = ?", [time()]);
        $job2 = $this->queue->pop();
        $this->assertNotNull($job2);
        $this->assertSame(1, $job2->attempts);
        $this->worker->processJob($job2);

        $this->assertSame(0, $this->jobCount('jobs'));
        $this->assertSame(1, $this->jobCount('failed_jobs'), 'failed after retries exhausted');
    }

    public function testDelayedJobIsNotPoppedBeforeAvailableAt(): void
    {
        $this->queue->push(new FailingJob(), 'default', delaySeconds: 3600);

        // Enqueued (counted), but not yet runnable until available_at.
        $this->assertSame(1, $this->queue->size(), 'delayed job is enqueued');
        $this->assertNull($this->queue->pop(), 'delayed job not popped before available_at');
    }

    public function testPopReservesSoASecondPopDoesNotReturnTheSameJob(): void
    {
        $this->queue->push(new FailingJob());

        $first = $this->queue->pop();
        $second = $this->queue->pop();

        $this->assertNotNull($first);
        $this->assertNull($second, 'a reserved job is not handed out twice');
    }

    public function testFailedJobCanBeListedAndRetried(): void
    {
        $this->queue->push(new FailingJob());
        $this->worker->processJob($this->queue->pop());

        $failed = $this->queue->failedJobs();
        $this->assertCount(1, $failed);
        $failedId = $failed[0]['id'];

        $this->assertTrue($this->queue->retryFailed($failedId));
        $this->assertSame(0, $this->jobCount('failed_jobs'), 'removed from failed_jobs');
        $this->assertSame(1, $this->jobCount('jobs'), 're-enqueued onto the live queue');

        // Re-enqueued job is poppable again.
        $this->assertNotNull($this->queue->pop());

        // Retrying an unknown id reports false.
        $this->assertFalse($this->queue->retryFailed('does-not-exist'));
    }

    private function jobCount(string $table): int
    {
        return (int) $this->db->query("SELECT COUNT(*) AS c FROM {$table}")[0]['c'];
    }
}

class FailingJob
{
    public function handle(): void
    {
        throw new \RuntimeException('boom');
    }
}

#[Retry(times: 2, backoff: 0)]
class RetryableJob
{
    public function handle(): void
    {
        throw new \RuntimeException('still failing');
    }
}
