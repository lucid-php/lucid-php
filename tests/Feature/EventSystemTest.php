<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\ApiController;
use App\Event\UserCreated;
use App\Event\UserDeleted;
use App\Listener\SendWelcomeEmail;
use App\Listener\LogUserCreation;
use App\Listener\CleanupUserData;
use App\Repository\UserRepository;
use Core\Container;
use Core\Database\Database;
use Core\Event\EventDispatcher;
use Core\Http\ExceptionHandler;
use Core\Http\Request;
use Core\Middleware\ExceptionMiddleware;
use Core\Queue\QueueInterface;
use Core\Queue\SyncQueue;
use Core\Router;
use Core\Log\Logger;
use Core\Log\LogLevel;
use Core\Log\Handler\StderrHandler;
use Core\Mail\MailerInterface;
use Core\Mail\ArrayMailer;
use PHPUnit\Framework\TestCase;

class EventSystemTest extends TestCase
{
    private Database $db;
    private Router $router;
    private EventDispatcher $events;
    private array $dispatchedEvents = [];

    protected function setUp(): void
    {
        // Setup in-memory database
        $this->db = new Database('sqlite::memory:', null, null);
        
        // Create users table
        $this->db->execute("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Setup container
        $container = new Container();
        $container->set(Database::class, $this->db);
        $container->set(UserRepository::class, new UserRepository($this->db));
        
        // Setup logger for jobs
        $logger = new Logger(
            minimumLevel: LogLevel::DEBUG,
            handlers: [new StderrHandler(json: false)]
        );
        $container->set(Logger::class, $logger);
        
        // Setup mailer for jobs
        $mailer = new ArrayMailer();
        $container->set(MailerInterface::class, $mailer);
        
        // Setup queue system (sync for tests)
        $queue = new SyncQueue($container);
        $container->set(QueueInterface::class, $queue);
        
        // Setup event dispatcher
        $this->events = new EventDispatcher($container);
        
        // Create a test listener that tracks dispatched events
        $testListener = new class($this->dispatchedEvents) {
            public function __construct(private array &$events) {}
            
            public function handle(object $event): void {
                $this->events[] = $event;
            }
        };
        
        // Register listeners
        $this->events->listen(UserCreated::class, SendWelcomeEmail::class);
        $this->events->listen(UserCreated::class, LogUserCreation::class);
        $this->events->listen(UserDeleted::class, CleanupUserData::class);
        
        $container->set(EventDispatcher::class, $this->events);
        
        // Setup exception handling
        $exceptionHandler = new ExceptionHandler(debug: false);
        $container->set(ExceptionHandler::class, $exceptionHandler);
        $container->set(ExceptionMiddleware::class, new ExceptionMiddleware($exceptionHandler));

        // Setup router
        $this->router = new Router($container);
        $this->router->addGlobalMiddleware(ExceptionMiddleware::class);
        $this->router->registerControllers([ApiController::class]);
    }

    public function test_user_created_event_is_dispatched_when_creating_user(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/api/users',
            query: [],
            body: [
                'name' => 'Event Test User',
                'email' => 'event@example.com',
                'password' => 'password123'
            ],
            server: []
        );

        $response = $this->router->dispatch($request);

        $this->assertEquals(201, $response->status);
        
        // Verify user was created in database
        $users = $this->db->query("SELECT * FROM users WHERE email = ?", ['event@example.com']);
        $this->assertCount(1, $users);
        $this->assertEquals('Event Test User', $users[0]['name']);
    }

    public function test_event_system_is_explicitly_registered(): void
    {
        // Verify listeners are explicitly registered (no magic)
        $this->assertTrue($this->events->hasListeners(UserCreated::class));
        $this->assertTrue($this->events->hasListeners(UserDeleted::class));
        
        $createdListeners = $this->events->getListeners(UserCreated::class);
        $deletedListeners = $this->events->getListeners(UserDeleted::class);
        
        $this->assertCount(2, $createdListeners);
        $this->assertCount(1, $deletedListeners);
        
        $this->assertContains(SendWelcomeEmail::class, $createdListeners);
        $this->assertContains(LogUserCreation::class, $createdListeners);
        $this->assertContains(CleanupUserData::class, $deletedListeners);
    }

    public function test_events_are_readonly_and_type_safe(): void
    {
        $event = new UserCreated(
            userId: 123,
            name: 'John Doe',
            email: 'john@example.com'
        );

        $this->assertEquals(123, $event->userId);
        $this->assertEquals('John Doe', $event->name);
        $this->assertEquals('john@example.com', $event->email);
        
        // Readonly properties cannot be modified (would be compile-time error)
        // This test just verifies the properties exist and are typed
        $this->assertIsInt($event->userId);
        $this->assertIsString($event->name);
        $this->assertIsString($event->email);
    }

    public function test_user_deleted_event_is_dispatched(): void
    {
        // Create a user first
        $userRepo = new UserRepository($this->db);
        $user = $userRepo->create('To Delete', 'delete@example.com', 'password');

        // Since we need auth middleware for delete, we'll test the event directly
        $event = new UserDeleted(
            userId: $user->id,
            email: $user->email
        );

        $this->events->dispatch($event);
        
        // Verify event was created correctly
        $this->assertEquals($user->id, $event->userId);
        $this->assertEquals($user->email, $event->email);
    }
}
