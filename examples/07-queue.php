<?php

declare(strict_types=1);

/**
 * Example 7: Queue System
 * 
 * Demonstrates:
 * - Creating job classes
 * - Dispatching jobs
 * - Queue drivers (Sync, Database)
 * - Background processing
 * - Failed jobs
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Queue\SyncQueue;
use Core\Queue\DatabaseQueue;
use Core\Queue\QueueInterface;
use Core\Container;
use Core\Database\Database;

// ===========================
// Example Job Classes
// ===========================

// Simple job with no dependencies
class SendNotificationJob
{
    public function __construct(
        private string $userId,
        private string $message
    ) {}
    
    public function handle(): void
    {
        echo "[Job] Sending notification to user #{$this->userId}\n";
        echo "      Message: {$this->message}\n";
        sleep(1); // Simulate work
        echo "      ✓ Notification sent!\n\n";
    }
}

// Job with dependency injection
class ProcessImageJob
{
    public function __construct(
        private string $imagePath,
        private array $sizes = ['thumbnail', 'medium', 'large']
    ) {}
    
    public function handle(): void
    {
        echo "[Job] Processing image: {$this->imagePath}\n";
        
        foreach ($this->sizes as $size) {
            echo "      Creating {$size} version...\n";
            sleep(1); // Simulate processing
        }
        
        echo "      ✓ Image processed!\n\n";
    }
}

// Job that uses services
class GenerateReportJob
{
    public function __construct(
        private string $reportType,
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $endDate
    ) {}
    
    public function handle(): void
    {
        echo "[Job] Generating {$this->reportType} report\n";
        echo "      Period: {$this->startDate->format('Y-m-d')} to {$this->endDate->format('Y-m-d')}\n";
        
        // Simulate report generation
        echo "      Fetching data from database...\n";
        sleep(2);
        
        echo "      Generating PDF...\n";
        sleep(1);
        
        echo "      ✓ Report generated!\n\n";
    }
}

// Job that can fail
class ImportUsersJob
{
    public function __construct(
        private string $filePath,
        private int $batchSize = 100
    ) {}
    
    public function handle(): void
    {
        echo "[Job] Importing users from: {$this->filePath}\n";
        echo "      Batch size: {$this->batchSize}\n";
        
        // Simulate file reading
        if (!file_exists($this->filePath)) {
            throw new \Exception("File not found: {$this->filePath}");
        }
        
        echo "      Processing batches...\n";
        sleep(2);
        
        echo "      ✓ Import completed!\n\n";
    }
}

// ===========================
// Example Usage
// ===========================

echo "Queue System Examples:\n";
echo "=====================\n\n";

// ===========================
// Example 1: Sync Driver (Immediate execution)
// ===========================

echo "=== Example 1: Sync Driver ===\n\n";
echo "Jobs execute immediately (no background worker needed)\n";
echo "Good for: Development, simple tasks\n\n";

$container = new Container();
$syncQueue = new SyncQueue($container);

echo "Dispatching jobs...\n\n";

$syncQueue->push(new SendNotificationJob('123', 'Your order has shipped!'));
$syncQueue->push(new ProcessImageJob('/uploads/photo.jpg'));

echo "All jobs completed immediately!\n\n";

// ===========================
// Example 2: Database Driver (Background processing)
// ===========================

echo "=== Example 2: Database Driver ===\n\n";
echo "Jobs are stored in database and processed by a worker\n";
echo "Good for: Production, long-running tasks\n\n";

// Note: Requires database connection
// $db = new Database($config);
// $dbQueue = new DatabaseQueue($db);

echo "Usage:\n";
echo "\$queue->push(new SendNotificationJob('123', 'Hello!'));\n";
echo "// Job is stored in 'jobs' table\n\n";

echo "Start the worker:\n";
echo "php console queue:work\n\n";

echo "Worker will:\n";
echo "  - Poll the database for pending jobs\n";
echo "  - Execute jobs one by one\n";
echo "  - Mark jobs as completed or failed\n";
echo "  - Retry failed jobs automatically\n\n";

// ===========================
// Example 3: Complex Job Chain
// ===========================

echo "=== Example 3: Job Chain ===\n\n";

class OrderProcessingChain
{
    public function __construct(private QueueInterface $queue) {}
    
    public function processOrder(int $orderId): void
    {
        echo "Processing order #{$orderId}...\n\n";
        
        // Push multiple jobs
        $this->queue->push(new SendNotificationJob(
            userId: '123',
            message: 'Your order is being processed'
        ));
        
        $this->queue->push(new GenerateReportJob(
            reportType: 'invoice',
            startDate: new \DateTimeImmutable(),
            endDate: new \DateTimeImmutable()
        ));
        
        $this->queue->push(new SendNotificationJob(
            userId: '123',
            message: 'Your order is complete!'
        ));
        
        echo "All jobs pushed!\n\n";
    }
}

$chain = new OrderProcessingChain($syncQueue);
$chain->processOrder(456);

// ===========================
// Example 4: Scheduled Jobs
// ===========================

echo "=== Example 4: Scheduled Jobs ===\n\n";

echo "Combine with Scheduler for recurring jobs:\n\n";

echo "// In config/schedule.php\n";
echo "\$scheduler->job(new GenerateReportJob(\n";
echo "    reportType: 'daily_sales',\n";
echo "    startDate: new DateTimeImmutable('-1 day'),\n";
echo "    endDate: new DateTimeImmutable()\n";
echo "))->daily();\n\n";

echo "\$scheduler->job(new ImportUsersJob('/data/users.csv'))\n";
echo "         ->cron('0 2 * * *'); // Every day at 2 AM\n\n";

// ===========================
// Example 5: Error Handling
// ===========================

echo "=== Example 5: Error Handling ===\n\n";

echo "Jobs with errors are automatically retried:\n\n";

try {
    $syncQueue->push(new ImportUsersJob('/nonexistent/file.csv'));
} catch (\Exception $e) {
    echo "Job failed: {$e->getMessage()}\n\n";
}

echo "In production (with DatabaseDriver):\n";
echo "  - Failed jobs are logged to 'failed_jobs' table\n";
echo "  - View failed jobs: php console queue:failed\n";
echo "  - Retry failed job: php console queue:retry <id>\n";
echo "  - Clear failed jobs: php console queue:flush\n\n";

// ===========================
// Configuration
// ===========================

echo "=== Configuration (config/queue.php) ===\n\n";

echo "return [\n";
echo "    'default' => env('QUEUE_DRIVER', 'sync'),\n";
echo "    'connections' => [\n";
echo "        'sync' => [\n";
echo "            'driver' => 'sync',\n";
echo "        ],\n";
echo "        'database' => [\n";
echo "            'driver' => 'database',\n";
echo "            'table' => 'jobs',\n";
echo "            'queue' => 'default',\n";
echo "        ],\n";
echo "    ],\n";
echo "];\n\n";

// ===========================
// Best Practices
// ===========================

echo "=== Best Practices ===\n\n";

echo "1. Keep jobs small and focused\n";
echo "   ✓ One responsibility per job\n";
echo "   ✗ Don't create mega-jobs that do everything\n\n";

echo "2. Make jobs idempotent\n";
echo "   ✓ Can be run multiple times safely\n";
echo "   ✗ Don't assume job will only run once\n\n";

echo "3. Use constructor for configuration\n";
echo "   ✓ Pass data in constructor\n";
echo "   ✗ Don't fetch data from database in constructor\n\n";

echo "4. Inject services in handle() method\n";
echo "   ✓ handle(Database \$db, Mail \$mail)\n";
echo "   ✗ Don't serialize services in constructor\n\n";

echo "5. Handle failures gracefully\n";
echo "   ✓ Try/catch around external services\n";
echo "   ✓ Log errors properly\n";
echo "   ✓ Return meaningful error messages\n";
