# Framework Examples

Complete examples demonstrating all major features of Lucid-PHP.

---

## 📋 Table of Contents

1. **[01-routing-basics.php](01-routing-basics.php)** - Routing & Controllers
2. **[02-middleware.php](02-middleware.php)** - Middleware (Global, Class, Method-level)
3. **[03-validation.php](03-validation.php)** - Validation with DTOs
4. **[04-database-repository.php](04-database-repository.php)** - Database & Repository Pattern
5. **[05-events.php](05-events.php)** - Event System
6. **[06-mail.php](06-mail.php)** - Mail System (SMTP, Log, Array drivers)
7. **[07-queue.php](07-queue.php)** - Queue System & Background Jobs
8. **[08-scheduler.php](08-scheduler.php)** - Task Scheduler (Cron-like)
9. **[09-cache.php](09-cache.php)** - Cache System
10. **[10-collections.php](10-collections.php)** - Collections & Array Operations
11. **[11-session-flash.php](11-session-flash.php)** - Session Management & Flash Messages
12. **[12-console.php](12-console.php)** - Console Commands (CLI)
13. **[13-http-client.php](13-http-client.php)** - HTTP Client & API Requests
14. **[14-upload.php](14-upload.php)** - File Upload & Validation
15. **[15-security.php](15-security.php)** - Security (CSRF, Rate Limiting)
16. **[16-api-responses.php](16-api-responses.php)** - Standardized API Responses

---

## 🚀 Quick Start

Each example is a standalone PHP file that demonstrates a specific feature. Run any example directly:

```bash
php examples/01-routing-basics.php
php examples/05-events.php
php examples/10-collections.php
```

---

## 📖 Example Overview

### 01. Routing Basics

**What it demonstrates:**
- Attribute-based routing with `#[Route]`
- HTTP methods (GET, POST, PUT, DELETE)
- Route prefixes with `#[RoutePrefix]`
- Request/Response handling
- RESTful API patterns

**Key concepts:**
```php
#[RoutePrefix('/api/posts')]
class PostController {
    #[Route('GET', '/')]
    public function index(Request $request): Response {
        return Response::json($posts);
    }
}
```

---

### 02. Middleware

**What it demonstrates:**
- Creating custom middleware
- Global middleware (all routes)
- Class-level middleware (all methods in controller)
- Method-level middleware (specific route)
- Middleware execution order

**Key concepts:**
```php
// Global
$router->addGlobalMiddleware(LoggingMiddleware::class);

// Class-level
#[Middleware(ApiKeyMiddleware::class)]
class SecureController { ... }

// Method-level
#[Middleware(AdminOnlyMiddleware::class)]
public function deleteUser() { ... }
```

---

### 03. Validation

**What it demonstrates:**
- Attribute-based validation rules
- Data Transfer Objects (DTOs)
- Built-in validation rules
- Automatic validation in controllers
- Error handling (422 responses)

**Key concepts:**
```php
class CreateUserDTO implements ValidatedDTO {
    #[Validate('required|email')]
    public string $email;
    
    #[Validate('required|min:8|password')]
    public string $password;
}

#[Route('POST', '/users')]
public function create(CreateUserDTO $dto): Response { ... }
```

---

### 04. Database & Repository

**What it demonstrates:**
- Raw SQL queries with PDO
- Repository pattern
- Database transactions
- CRUD operations
- Complex queries with parameters

**Key concepts:**
```php
class UserRepository {
    public function findById(int $id): ?array {
        $stmt = $this->db->query(
            'SELECT * FROM users WHERE id = :id',
            ['id' => $id]
        );
        return $stmt->fetch() ?: null;
    }
}

// Transactions
$db->transaction(function() use ($db) {
    $db->query('INSERT ...');
    $db->query('UPDATE ...');
    return $result;
});
```

---

### 05. Events

**What it demonstrates:**
- Creating event classes
- Creating listener classes
- Registering listeners
- Dispatching events
- Multiple listeners for same event
- Decoupling application logic

**Key concepts:**
```php
class UserRegisteredEvent {
    public function __construct(
        public readonly int $userId,
        public readonly string $email
    ) {}
}

$dispatcher->listen(UserRegisteredEvent::class, SendWelcomeEmailListener::class);
$dispatcher->dispatch(new UserRegisteredEvent(123, 'user@example.com'));
```

---

### 06. Mail

**What it demonstrates:**
- Sending emails via SMTP
- Different mail drivers (SMTP, Log, Array)
- HTML emails
- Multiple recipients (To, CC, BCC)
- Queued emails
- Email templates

**Key concepts:**
```php
$mail->to('user@example.com')
     ->subject('Welcome!')
     ->body($htmlContent)
     ->send();

// Queued
$queue->dispatch(new SendWelcomeEmailJob('user@example.com', 'John'));
```

---

### 07. Queue

**What it demonstrates:**
- Creating job classes
- Dispatching jobs to queue
- Sync driver (immediate execution)
- Database driver (background processing)
- Job dependency injection
- Error handling and retries

**Key concepts:**
```php
class ProcessImageJob {
    public function __construct(private string $imagePath) {}
    
    public function handle(): void {
        // Process image
    }
}

$queue->dispatch(new ProcessImageJob('/uploads/photo.jpg'));
```

---

### 08. Scheduler

**What it demonstrates:**
- Cron-like task scheduling
- Scheduled job classes
- Cron expressions (standard 5-part syntax)
- Helper methods (daily, hourly, weekly)
- Production setup with crontab
- Timezone handling

**Key concepts:**
```php
class BackupDatabaseJob implements ScheduledJobInterface {
    public function schedule(): CronExpression {
        return CronExpression::daily(); // 0 0 * * *
    }
    
    public function execute(OutputInterface $output): void {
        // Create backup
    }
}

// In crontab: * * * * * cd /path && php console schedule:run
```

---

### 09. Cache

**What it demonstrates:**
- Storing and retrieving cached data
- Cache drivers (File, Array)
- Cache expiration (TTL)
- Remember pattern
- Cache tags
- Increment/Decrement
- Real-world use cases (API responses, rate limiting)

**Key concepts:**
```php
// Basic
$cache->put('key', $value, 3600);
$value = $cache->get('key');

// Remember pattern
$posts = $cache->remember('posts:all', 600, function() {
    return $db->query('SELECT * FROM posts')->fetchAll();
});

// Tags
$cache->tags(['users'])->put('user:123', $user, 3600);
$cache->tags(['users'])->flush();
```

---

### 10. Collections

**What it demonstrates:**
- Fluent array manipulation
- Filtering and mapping
- Sorting (ascending/descending)
- Grouping by key
- Aggregations (sum, avg, max, min)
- Chaining operations
- Real-world data transformations

**Key concepts:**
```php
$engineers = $collection
    ->filter(fn($user) => $user['department'] === 'Engineering')
    ->filter(fn($user) => $user['salary'] > 55000)
    ->sortByDesc('salary')
    ->map(fn($user) => $user['name']);

$avgSalary = $collection->avg('salary');
$byDept = $collection->groupBy('department');
```

---

## 🎯 Running Examples

### Prerequisites

```bash
# Install dependencies
composer install

# Set up database (for database examples)
php console migrate
```

### Running Individual Examples

```bash
# Just view the code and output
php examples/01-routing-basics.php
php examples/05-events.php
php examples/09-cache.php

# Examples that need the full app running
# (routing, middleware, validation)
php -S localhost:8000 -t public
curl http://localhost:8000/api/posts
```

---

## 📚 Related Documentation

For detailed information about each feature:

- **[Getting Started](../documentation/01-getting-started.md)** - Installation & setup
- **[Routing](../documentation/05-routing.md)** - Full routing documentation
- **[Database](../documentation/06-database.md)** - Database operations
- **[Validation](../documentation/07-validation.md)** - Validation rules
- **[Events](../documentation/08-events.md)** - Event system
- **[Mail](../documentation/09-mail.md)** - Email sending
- **[Queue](../documentation/10-queue.md)** - Background jobs
- **[Scheduler](../documentation/11-scheduler.md)** - Task scheduling
- **[Console](../documentation/12-console.md)** - CLI commands

---

## 🧪 Testing

Many examples include patterns suitable for unit testing:

```php
// Test events
$arrayDriver = new ArrayDriver();
$mail = new Mail($arrayDriver);

$mail->to('test@example.com')->subject('Test')->body('Body')->send();

$sent = $arrayDriver->getSent();
$this->assertCount(1, $sent);
```

---

## 💡 Tips

1. **Start with basics**: Begin with examples 01-03 to understand routing, middleware, and validation
2. **Run examples**: Execute the PHP files to see actual output
3. **Modify and experiment**: Change values and see what happens
4. **Check the docs**: Each example links to detailed documentation
5. **Look at tests**: See `tests/` directory for more usage patterns

---

## 11. 🔐 Session & Flash Messages

**File:** `11-session-flash.php`

**What it demonstrates:**
- Session management (start, get, set, has, remove)
- Flash messages for one-time notifications
- Session security (regeneration, secure cookies)
- Shopping cart example
- Login flow with session management

**Key Concepts:**
```php
// Session management
$session = new Session(['name' => 'app_session']);
$session->start();
$session->set('user_id', 123);

// Flash messages (auto-removed after retrieval)
$flash = new FlashMessages($session);
$flash->success('Operation successful!');
$messages = $flash->get('success');
```

---

## 12. 🖥️ Console Commands

**File:** `12-console.php`

**What it demonstrates:**
- Creating CLI commands
- Arguments and options
- Output formatting (success, error, info, warning)
- Built-in framework commands
- Exit codes and error handling

**Key Concepts:**
```php
class GreetCommand implements CommandInterface
{
    public function getName(): string { return 'greet'; }
    
    public function execute(Input $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name', 'World');
        $output->success("Hello, $name!");
        return 0; // Success
    }
}
```

**Usage:** `php console greet Magnus`

---

## 13. 🌐 HTTP Client

**File:** `13-http-client.php`

**What it demonstrates:**
- HTTP requests (GET, POST, PUT, DELETE)
- JSON handling
- Authentication (Bearer, Basic)
- MockHttpClient for testing

**Key Concepts:**
```php
$client = new CurlHttpClient();

$request = HttpRequest::post('https://api.example.com/users', json_encode($data))
    ->asJson()
    ->withBearerToken('token');

$response = $client->send($request);
$data = $response->json();
```

---

## 14. 📤 File Upload

**File:** `14-upload.php`

**What it demonstrates:**
- File upload handling
- Validation (size, type, dimensions)
- Secure storage strategies
- Multiple file uploads

**Key Concepts:**
```php
$handler = new FileUploadHandler(__DIR__ . '/../storage/uploads');

$handler->maxSize(5 * 1024 * 1024); // 5MB
$handler->allowedTypes(['image/jpeg', 'image/png']);
$handler->maxWidth(1000);

$path = $handler->store($file, 'avatars');
```

---

## 15. 🔒 Security Features

**File:** `15-security.php`

**What it demonstrates:**
- CSRF protection
- Rate limiting
- XSS prevention
- Password hashing
- Security headers

**Key Concepts:**
```php
// CSRF
$csrf = new CsrfTokenManager($session);
$token = $csrf->generateToken();

// Rate Limiting
#[RateLimit(maxAttempts: 10, window: 60)]
public function apiEndpoint() { }

// Password Security
$hash = password_hash($password, PASSWORD_ARGON2ID);
```

---

## 16. 📡 Standardized API Responses

**File:** `16-api-responses.php`

**What it demonstrates:**
- Consistent API response structure
- Success, error, and paginated responses
- HTTP status code handling
- Explicit response creation
- Type-safe response handling

**Key Concepts:**
```php
// Success response
return ApiResponse::success(
    data: $users,
    message: 'Users retrieved successfully'
);

// Error responses
return ApiResponse::notFound('User not found');
return ApiResponse::validationError($errors);
return ApiResponse::unauthorized();

// Paginated response
return ApiResponse::paginated(
    items: $users,
    total: 100,
    page: 1,
    perPage: 10
);

// Standard structure:
{
  "success": bool,
  "data": mixed,
  "message": string|null,
  "errors": array|null,
  "meta": array|null
}
```

---

## 🔍 What's NOT Included

These examples focus on framework features. They do NOT demonstrate:

- **Frontend/UI**: This is a backend framework
- **ORMs**: We use raw SQL and repositories (see 04-database-repository.php)
- **Auto-magic features**: Everything is explicit (see [Philosophy](../documentation/02-philosophy.md))
- **Service auto-discovery**: All services are manually registered

---

## 🤔 Need Help?

1. Check the [documentation](../documentation/)
2. Look at the [tests](../tests/) for more examples
3. Review the [philosophy](../documentation/02-philosophy.md) to understand design decisions

---

## 📋 Quick Reference

| Feature | Example | Command |
|---------|---------|---------|
| Routing | 01, 02 | `php -S localhost:8000 -t public` |
| Database | 04 | `php console migrate` |
| Events | 05 | `php examples/05-events.php` |
| Mail | 06 | Configure in `config/mail.php` |
| Queue | 07 | `php console queue:work` |
| Scheduler | 08 | `php console schedule:run` |
| Cache | 09 | `php examples/09-cache.php` |
| Collections | 10 | `php examples/10-collections.php` |
| Session | 11 | `php examples/11-session-flash.php` |
| Console | 12 | `php examples/12-console.php` |
| HTTP Client | 13 | `php examples/13-http-client.php` |
| Upload | 14 | `php examples/14-upload.php` |
| Security | 15 | `php examples/15-security.php` |
| API Responses | 16 | `php examples/16-api-responses.php` |


---

**Remember**: This framework follows the "Explicit over Implicit" philosophy. Every example shows you exactly what's happening with no hidden magic! 🎯
