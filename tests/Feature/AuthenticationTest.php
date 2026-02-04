<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Core\Container;
use Core\Database\Database;
use Core\Http\ExceptionHandler;
use Core\Http\Request;
use Core\Middleware\ExceptionMiddleware;
use Core\Router;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    private Database $db;
    private Router $router;
    private UserRepository $userRepo;
    private TokenRepository $tokenRepo;

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

        // Create tokens table
        $this->db->execute("
            CREATE TABLE personal_access_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // Setup repositories
        $this->userRepo = new UserRepository($this->db);
        $this->tokenRepo = new TokenRepository($this->db);

        // Setup container with dependencies
        $container = new Container();
        $container->set(Database::class, $this->db);
        $container->set(UserRepository::class, $this->userRepo);
        $container->set(TokenRepository::class, $this->tokenRepo);
        
        // Setup exception handling
        $exceptionHandler = new ExceptionHandler(debug: false);
        $container->set(ExceptionHandler::class, $exceptionHandler);
        $container->set(ExceptionMiddleware::class, new ExceptionMiddleware($exceptionHandler));

        // Setup router and register controllers
        $this->router = new Router($container);
        $this->router->addGlobalMiddleware(ExceptionMiddleware::class);
        $this->router->registerControllers([
            AuthController::class,
            ApiController::class
        ]);
    }

    protected function tearDown(): void
    {
        unset($this->db, $this->router, $this->userRepo, $this->tokenRepo);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        // Create a test user
        $this->userRepo->create('John Doe', 'john@example.com', 'password123');

        // Create login request
        $request = new Request(
            method: 'POST',
            uri: '/auth/login',
            query: [],
            body: [
                'email' => 'john@example.com',
                'password' => 'password123'
            ],
            server: []
        );

        // Get response from router
        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(200, $response->status);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('token', $data);
        $this->assertEquals('Login successful', $data['message']);
        $this->assertNotEmpty($data['token']);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        // Create a test user
        $this->userRepo->create('John Doe', 'john@example.com', 'password123');

        // Create login request with wrong password
        $request = new Request(
            method: 'POST',
            uri: '/auth/login',
            query: [],
            body: [
                'email' => 'john@example.com',
                'password' => 'wrongpassword'
            ],
            server: []
        );

        // Get response
        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(401, $response->status);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Unauthorized', $data['error']);
        $this->assertEquals('Invalid credentials', $data['message']);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        // Create login request with non-existent email
        $request = new Request(
            method: 'POST',
            uri: '/auth/login',
            query: [],
            body: [
                'email' => 'nonexistent@example.com',
                'password' => 'password123'
            ],
            server: []
        );

        // Get response
        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(401, $response->status);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Unauthorized', $data['error']);
        $this->assertEquals('Invalid credentials', $data['message']);
    }

    public function test_protected_route_requires_valid_token(): void
    {
        // Create user and token
        $user = $this->userRepo->create('Jane Doe', 'jane@example.com', 'password123');
        $token = $this->tokenRepo->createToken($user->id);

        // Create authenticated request to /api/me
        $request = new Request(
            method: 'GET',
            uri: '/api/me',
            query: [],
            body: [],
            server: ['HTTP_AUTHORIZATION' => "Bearer $token"]
        );

        // Get response
        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(200, $response->status);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertEquals('Jane Doe', $data['name']);
        $this->assertEquals('jane@example.com', $data['email']);
    }

    public function test_protected_route_rejects_invalid_token(): void
    {
        // Create request with invalid token
        $request = new Request(
            method: 'GET',
            uri: '/api/me',
            query: [],
            body: [],
            server: ['HTTP_AUTHORIZATION' => 'Bearer invalid-token-12345']
        );

        // Get response
        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(401, $response->status);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Unauthorized', $data['error']);
        $this->assertEquals('Invalid Token', $data['message']);
    }

    public function test_protected_route_rejects_missing_token(): void
    {
        // Create request without Authorization header
        $request = new Request(
            method: 'GET',
            uri: '/api/me',
            query: [],
            body: [],
            server: []
        );

        // Get response
        $response = $this->router->dispatch($request);
        $data = json_decode($response->content, true);

        $this->assertEquals(401, $response->status);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Unauthorized', $data['error']);
        $this->assertEquals('Missing or Invalid Token', $data['message']);
    }

    public function test_full_authentication_flow(): void
    {
        // 1. Create a user
        $this->userRepo->create('Bob Smith', 'bob@example.com', 'secret123');

        // 2. Login and get token
        $loginRequest = new Request(
            method: 'POST',
            uri: '/auth/login',
            query: [],
            body: [
                'email' => 'bob@example.com',
                'password' => 'secret123'
            ],
            server: []
        );

        $loginResponse = $this->router->dispatch($loginRequest);
        $loginData = json_decode($loginResponse->content, true);

        $this->assertEquals(200, $loginResponse->status);
        $this->assertArrayHasKey('token', $loginData);
        $token = $loginData['token'];

        // 3. Use token to access protected route
        $meRequest = new Request(
            method: 'GET',
            uri: '/api/me',
            query: [],
            body: [],
            server: ['HTTP_AUTHORIZATION' => "Bearer $token"]
        );

        $meResponse = $this->router->dispatch($meRequest);
        $meData = json_decode($meResponse->content, true);

        $this->assertEquals(200, $meResponse->status);
        $this->assertEquals('Bob Smith', $meData['name']);
        $this->assertEquals('bob@example.com', $meData['email']);
    }
}
