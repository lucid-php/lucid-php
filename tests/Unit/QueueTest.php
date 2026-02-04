<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Job\SendWelcomeEmailJob;
use App\Job\ProcessOrderJob;
use Core\Container;
use Core\Database\Database;
use Core\Queue\DatabaseQueue;
use Core\Queue\SyncQueue;
use Core\Queue\QueueWorker;
use Core\Log\Logger;
use Core\Log\LogLevel;
use Core\Log\Handler\StderrHandler;
use Core\Mail\MailerInterface;
use Core\Mail\ArrayMailer;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    private Database $db;
    private Container $container;

    protected function setUp(): void
    {
        // Setup in-memory database
        $this->db = new Database('sqlite::memory:', null, null);
        
        // Create jobs table
        $this->db->execute("
            CREATE TABLE jobs (
                id TEXT PRIMARY KEY,
                queue TEXT NOT NULL DEFAULT 'default',
                payload TEXT NOT NULL,
                attempts INTEGER NOT NULL DEFAULT 0,
                available_at INTEGER NOT NULL,
                created_at INTEGER NOT NULL
            )
        ");

        // Create index
        $this->db->execute("
            CREATE INDEX idx_jobs_queue_available ON jobs(queue, available_at)
        ");

        // Setup container
        $this->container = new Container();
        $this->container->set(Database::class, $this->db);
        
        // Setup logger for jobs
        $logger = new Logger(
            minimumLevel: LogLevel::DEBUG,
            handlers: [new StderrHandler(json: false)]
        );
        $this->container->set(Logger::class, $logger);
        
        // Setup mailer for jobs
        $mailer = new ArrayMailer();
        $this->container->set(MailerInterface::class, $mailer);
    }

    public function test_database_queue_can_push_and_pop_jobs(): void
    {
        $queue = new DatabaseQueue($this->db);
        
        $job = new SendWelcomeEmailJob(
            userId: 123,
            name: 'John Doe',
            email: 'john@example.com'
        );

        // Push job
        $queue->push($job);

        // Verify queue size
        $this->assertEquals(1, $queue->size());

        // Pop job
        $queuedJob = $queue->pop();

        $this->assertNotNull($queuedJob);
        $this->assertInstanceOf(SendWelcomeEmailJob::class, $queuedJob->job);
        $this->assertEquals(123, $queuedJob->job->userId);
        $this->assertEquals('John Doe', $queuedJob->job->name);
        $this->assertEquals('john@example.com', $queuedJob->job->email);
        $this->assertEquals(0, $queue->size());
    }

    public function test_database_queue_respects_queue_names(): void
    {
        $queue = new DatabaseQueue($this->db);

        $emailJob = new SendWelcomeEmailJob(123, 'John', 'john@example.com');
        $orderJob = new ProcessOrderJob(456, 99.99, []);

        $queue->push($emailJob, 'emails');
        $queue->push($orderJob, 'orders');

        $this->assertEquals(1, $queue->size('emails'));
        $this->assertEquals(1, $queue->size('orders'));

        $poppedEmail = $queue->pop('emails');
        $poppedOrder = $queue->pop('orders');

        $this->assertInstanceOf(SendWelcomeEmailJob::class, $poppedEmail->job);
        $this->assertInstanceOf(ProcessOrderJob::class, $poppedOrder->job);
    }

    public function test_database_queue_returns_null_when_empty(): void
    {
        $queue = new DatabaseQueue($this->db);

        $this->assertNull($queue->pop());
        $this->assertEquals(0, $queue->size());
    }

    public function test_database_queue_can_clear(): void
    {
        $queue = new DatabaseQueue($this->db);

        $queue->push(new SendWelcomeEmailJob(1, 'A', 'a@example.com'));
        $queue->push(new SendWelcomeEmailJob(2, 'B', 'b@example.com'));

        $this->assertEquals(2, $queue->size());

        $queue->clear();

        $this->assertEquals(0, $queue->size());
        $this->assertNull($queue->pop());
    }

    public function test_sync_queue_executes_jobs_immediately(): void
    {
        $queue = new SyncQueue($this->container);

        $job = new ProcessOrderJob(
            orderId: 999,
            total: 199.99,
            items: []
        );

        // Job executes immediately
        $queue->push($job);

        // Sync queue is always empty
        $this->assertEquals(0, $queue->size());
        $this->assertNull($queue->pop());
    }

    public function test_worker_can_process_job_with_dependencies(): void
    {
        $queue = new DatabaseQueue($this->db);
        $worker = new QueueWorker($queue, $this->container);

        $job = new SendWelcomeEmailJob(
            userId: 777,
            name: 'Worker Test',
            email: 'worker@example.com'
        );

        $queue->push($job);

        $queuedJob = $queue->pop();
        $this->assertNotNull($queuedJob);

        // Should not throw exception
        $worker->processJob($queuedJob);

        $this->assertTrue(true);
    }

    public function test_multiple_jobs_processed_in_order(): void
    {
        $queue = new DatabaseQueue($this->db);

        // Push jobs in order
        $queue->push(new ProcessOrderJob(1, 10.0, []));
        $queue->push(new ProcessOrderJob(2, 20.0, []));
        $queue->push(new ProcessOrderJob(3, 30.0, []));

        $this->assertEquals(3, $queue->size());

        // Pop in FIFO order
        $job1 = $queue->pop();
        $job2 = $queue->pop();
        $job3 = $queue->pop();

        $this->assertEquals(1, $job1->job->orderId);
        $this->assertEquals(2, $job2->job->orderId);
        $this->assertEquals(3, $job3->job->orderId);
    }
}
