# Queue System

The framework provides an **explicit job queue** for background processing. Jobs are strongly-typed classes, queues are explicitly configured, and there's no magic auto-discovery.

## Philosophy

- **No Magic:** Jobs explicitly dispatched—no auto-queuing or hidden behavior
- **Typed Jobs:** Jobs are readonly classes with typed properties (Command+Click works)
- **Explicit Dispatch:** You call `$queue->push(new Job(...))` where you want it
- **Driver-Based:** Choose sync (immediate) or database (background) explicitly
- **Zero Hidden Behavior:** No global `dispatch()` helper, no silent failures

## Core Concepts

**Jobs are Task Containers:**
- Readonly classes that carry task data
- `handle()` method does the work
- Dependencies injected into `handle()` method

**Queue is the Buffer:**
- Stores jobs until workers process them
- Multiple named queues (default, emails, processing)
- Configurable driver (sync or database)

**Worker Processes Jobs:**
- Pulls jobs from queue
- Resolves job dependencies from container
- Executes `handle()` method

## Setup

### 1. Configure Queue Driver in `config/queue.php`

```php
return [
    // 'sync' executes immediately (no queue, good for dev)
    // 'database' stores jobs in DB, requires worker
    'driver' => 'sync',  // or 'database'
    
    'default' => 'default',
    
    'queues' => [
        'default',
        'emails',
        'notifications',
        'processing',
    ],
];
```

### 2. Run Migration (for database driver)

```bash
php console migrate
```

### 3. Create Job Classes

```php
// src/App/Job/SendWelcomeEmailJob.php
namespace App\Job;

use Core\Log\Logger;

readonly class SendWelcomeEmailJob
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
    ) {}

    // Dependencies injected from container
    public function handle(Logger $logger): void
    {
        // Send email...
        $logger->info('Welcome email sent', [
            'user_id' => $this->userId,
            'email' => $this->email
        ]);
    }
}
```

### 4. Dispatch Jobs

```php
use Core\Queue\QueueInterface;
use App\Job\SendWelcomeEmailJob;

class UserController
{
    public function __construct(
        private readonly QueueInterface $queue
    ) {}

    #[Route('POST', '/users')]
    public function createUser(CreateUserDTO $data): Response
    {
        $user = $this->users->create($data);

        // Dispatch job to queue
        $this->queue->push(new SendWelcomeEmailJob(
            userId: $user->id,
            name: $user->name,
            email: $user->email
        ));

        return Response::json(['id' => $user->id], 201);
    }
}
```

### 5. Run Queue Worker (database driver only)

```bash
# Process jobs from default queue
php console queue:work

# Process specific queue
php console queue:work --queue=emails

# Custom sleep time when queue is empty
php console queue:work --sleep=5
```

## Usage Examples

### Job with No Dependencies

```php
readonly class ProcessOrderJob
{
    public function __construct(
        public int $orderId,
        public float $total,
        public array $items
    ) {}

    // No dependencies needed
    public function handle(): void
    {
        // Process order logic...
    }
}
```

### Job with Multiple Dependencies

```php
readonly class GenerateReportJob
{
    public function __construct(
        public int $reportId,
        public string $format
    ) {}

    public function handle(
        Database $db,
        Logger $logger,
        FileStorage $storage
    ): void {
        $logger->info('Generating report', ['id' => $this->reportId]);
        
        // Query data
        $data = $db->query("SELECT * FROM reports WHERE id = ?", [$this->reportId]);
        
        // Generate report
        $pdf = $this->generatePdf($data);
        
        // Store file
        $storage->put("reports/{$this->reportId}.pdf", $pdf);
    }
}
```

### Dispatching to Named Queues

```php
// High-priority email queue
$queue->push(new SendWelcomeEmailJob(...), 'emails');

// Background processing queue
$queue->push(new ProcessOrderJob(...), 'processing');

// Default queue
$queue->push(new CleanupJob(...));  // Uses 'default'
```

### Integration with Events

```php
// Event listener that dispatches job
class SendWelcomeEmail
{
    public function __construct(
        private readonly QueueInterface $queue
    ) {}

    public function handle(UserCreated $event): void
    {
        // Don't block HTTP response - queue the work
        $this->queue->push(new SendWelcomeEmailJob(
            userId: $event->userId,
            name: $event->name,
            email: $event->email
        ));
    }
}
```

## Queue Drivers

### Sync Driver (Development/Testing)

```php
// config/queue.php
return ['driver' => 'sync'];
```

- Executes jobs **immediately** when pushed
- No queue worker needed
- Good for local development and testing
- No job persistence

### Database Driver (Production)

```php
// config/queue.php
return ['driver' => 'database'];
```

- Stores jobs in `jobs` table
- Requires queue worker to process
- Jobs persisted across restarts
- Supports multiple workers

## Queue Worker

### Start Worker

```bash
# Run continuously
php console queue:work

# Process specific queue
php console queue:work --queue=emails

# Sleep 5 seconds when empty (default: 3)
php console queue:work --sleep=5
```

### Worker Process

1. Pull next job from queue
2. Delete job from queue
3. Resolve job dependencies from container
4. Execute `job->handle(...dependencies)`
5. Log errors if job fails
6. Repeat

### Supervisor Configuration (Production)

```ini
[program:queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/console queue:work
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/queue-worker.log
```

## API Reference

### QueueInterface Methods

```php
// Push job to queue
$queue->push(object $job, string $queue = 'default'): void;

// Get queue size
$size = $queue->size(string $queue = 'default'): int;

// Clear all jobs from queue
$queue->clear(string $queue = 'default'): void;

// Pop next job (used internally by worker)
$queuedJob = $queue->pop(string $queue = 'default'): ?QueuedJob;
```

## Best Practices

### ✅ DO:
- Make jobs readonly classes with typed properties
- Keep job data minimal (IDs, not full objects)
- Name jobs as actions: `SendWelcomeEmailJob`, `ProcessOrderJob`
- Use dependency injection in `handle()` method
- Queue jobs from event listeners (don't block HTTP responses)
- Use named queues to prioritize work

### ❌ DON'T:
- Store large objects in jobs (use IDs and fetch from DB)
- Use magic methods like `__invoke()` (explicit `handle()` is better)
- Put business logic in job constructor (use `handle()` method)
- Queue jobs that must execute immediately (use sync code)
- Store sensitive data in job payload
- Run queue workers without supervisor/systemd in production

## Philosophy Compliance

✅ **Zero Magic:** All jobs explicitly dispatched—no auto-queuing  
✅ **Strict Typing:** Jobs are typed classes, not arrays or strings  
✅ **Explicit:** You call `push()` exactly where you want jobs queued  
✅ **Attributes-First:** N/A—queue is code-driven, not config-driven  
✅ **Modern PHP:** Uses readonly classes, constructor property promotion  
✅ **Traceable:** Command+Click on job class takes you to definition
