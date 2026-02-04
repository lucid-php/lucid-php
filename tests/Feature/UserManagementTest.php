<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\ApiController;
use App\Repository\UserRepository;
use Core\Container;
use Core\Database\Database;
use Core\Http\ExceptionHandler;
use Core\Http\Request;
use Core\Middleware\ExceptionMiddleware;
use Core\Router;
use PHPUnit\Framework\TestCase;

class UserManagementTest extends TestCase
{
    private Database $db;
    private Router $router;
    private UserRepository $userRepo;

    protected function setUp(): void
    {
        // Setup in-memory SQLite database
        $this->db = new Database('sqlite::memory:');

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

        // Setup repositories
        $this->userRepo = new UserRepository($this->db);

        // Setup container with dependencies
        $container = new Container();
        $container->set(Database::class, $this->db);
        $container->set(UserRepository::class, $this->userRepo);
        
        // Setup exception handling
        $exceptionHandler = new ExceptionHandler(debug: false);
        $container->set(ExceptionHandler::class, $exceptionHandler);
        $container->set(ExceptionMiddleware::class, new ExceptionMiddleware($exceptionHandler));

        // Setup router and register controllers
        $this->router = new Router($container);
        $this->router->addGlobalMiddleware(ExceptionMiddleware::class);
        $this->router->registerControllers([
            ApiController::class
        ]);
    }

    protected function tearDown(): void
    {
        unset($this->db, $this->router, $this->userRepo);
    }

    public function test_can_create_user_with_valid_data(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/api/users',
            query: [],
            body: [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'password' => 'securepassword'
            ],
            server: []
        );

        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(201, $response->status);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals('User created', $data['message']);
        $this->assertEquals('Alice Johnson', $data['user']['name']);
        $this->assertEquals('alice@example.com', $data['user']['email']);
        $this->assertArrayHasKey('id', $data['user']);
    }

    public function test_create_user_validates_required_fields(): void
    {
        // Missing name field
        $request = new Request(
            method: 'POST',
            uri: '/api/users',
            query: [],
            body: [
                'email' => 'test@example.com',
                'password' => 'password123'
            ],
            server: []
        );

        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(422, $response->status);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Validation Failed', $data['error']);
        $this->assertArrayHasKey('details', $data);
    }

    public function test_create_user_validates_email_format(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/api/users',
            query: [],
            body: [
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'password' => 'password123'
            ],
            server: []
        );

        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(422, $response->status);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Validation Failed', $data['error']);
        $this->assertArrayHasKey('details', $data);
        $this->assertArrayHasKey('email', $data['details']);
    }

    public function test_create_user_validates_password_length(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/api/users',
            query: [],
            body: [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => '123' // Too short (min is 6)
            ],
            server: []
        );

        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(422, $response->status);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Validation Failed', $data['error']);
        $this->assertArrayHasKey('details', $data);
        $this->assertArrayHasKey('password', $data['details']);
    }

    public function test_api_status_endpoint_returns_active(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/api/status',
            query: [],
            body: [],
            server: []
        );

        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(200, $response->status);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('service', $data);
        $this->assertEquals('active', $data['status']);
        $this->assertEquals('api', $data['service']);
    }

    public function test_created_user_is_persisted_in_database(): void
    {
        // Create user through API
        $request = new Request(
            method: 'POST',
            uri: '/api/users',
            query: [],
            body: [
                'name' => 'Database Test',
                'email' => 'dbtest@example.com',
                'password' => 'password123'
            ],
            server: []
        );

        $response = $this->router->dispatch($request);
        $this->assertEquals(201, $response->status);

        // Verify user exists in database
        $user = $this->userRepo->findByEmail('dbtest@example.com');

        $this->assertNotNull($user);
        $this->assertEquals('Database Test', $user->name);
        $this->assertEquals('dbtest@example.com', $user->email);
        
        // Verify password is hashed
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(password_verify('password123', $user->password));
    }

    public function test_404_for_nonexistent_route(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/api/nonexistent',
            query: [],
            body: [],
            server: []
        );

        $response = $this->router->dispatch($request);

        $this->assertEquals(404, $response->status);
        $this->assertStringContainsString('404 Not Found', $response->content);
    }

    public function test_multiple_users_can_be_created(): void
    {
        $users = [
            ['name' => 'User One', 'email' => 'user1@example.com', 'password' => 'password123'],
            ['name' => 'User Two', 'email' => 'user2@example.com', 'password' => 'password456'],
            ['name' => 'User Three', 'email' => 'user3@example.com', 'password' => 'password789']
        ];

        foreach ($users as $userData) {
            $request = new Request(
                method: 'POST',
                uri: '/api/users',
                query: [],
                body: $userData,
                server: []
            );

            $response = $this->router->dispatch($request);
            $this->assertEquals(201, $response->status);
        }

        // Verify all users exist
        $allUsers = $this->userRepo->findAll();
        
        $this->assertCount(3, $allUsers);
        $emails = array_map(fn($u) => $u->email, $allUsers);
        $this->assertContains('user1@example.com', $emails);
        $this->assertContains('user2@example.com', $emails);
        $this->assertContains('user3@example.com', $emails);
    }
}
