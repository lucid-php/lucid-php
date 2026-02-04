<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Middleware\CsrfMiddleware;
use Core\Security\CsrfTokenManager;
use Core\Security\Csrf;
use Core\Session\Session;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RequestHandlerInterface;
use Core\Http\ForbiddenException;
use PHPUnit\Framework\TestCase;

class CsrfMiddlewareTest extends TestCase
{
    private Session $session;
    private CsrfTokenManager $csrfManager;
    private CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        $this->session = new Session([
            'name' => 'test_csrf_mw_' . uniqid(),
            'use_cookies' => false,
        ]);
        $this->session->start();
        
        $this->csrfManager = new CsrfTokenManager($this->session);
        $this->middleware = new CsrfMiddleware($this->csrfManager);
    }

    protected function tearDown(): void
    {
        if ($this->session->isStarted()) {
            $this->session->destroy();
        }
    }

    public function testAllowsSafeMethodsWithoutToken(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/test',
            query: [],
            body: [],
            server: []
        );

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertSame(200, $response->status);
    }

    public function testAllowsHeadMethodWithoutToken(): void
    {
        $request = new Request(
            method: 'HEAD',
            uri: '/test',
            query: [],
            body: [],
            server: []
        );

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertSame(200, $response->status);
    }

    public function testAllowsOptionsMethodWithoutToken(): void
    {
        $request = new Request(
            method: 'OPTIONS',
            uri: '/test',
            query: [],
            body: [],
            server: []
        );

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertSame(200, $response->status);
    }

    public function testAllowsPostWithoutCsrfAttribute(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/test',
            query: [],
            body: [],
            server: []
        );
        // No controller/method attributes = no CSRF check

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertSame(200, $response->status);
    }

    public function testValidatesTokenInRequestBody(): void
    {
        $token = $this->csrfManager->generateToken();
        
        $request = new Request(
            method: 'POST',
            uri: '/test',
            query: [],
            body: ['_csrf_token' => $token],
            server: []
        );
        $request = $request
            ->withAttribute('controller', MockCsrfController::class)
            ->withAttribute('method', 'create');

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertSame(200, $response->status);
    }

    public function testValidatesTokenInHeader(): void
    {
        $token = $this->csrfManager->generateToken();
        
        $request = new Request(
            method: 'POST',
            uri: '/test',
            query: [],
            body: [],
            server: ['HTTP_X_CSRF_TOKEN' => $token]
        );
        $request = $request
            ->withAttribute('controller', MockCsrfController::class)
            ->withAttribute('method', 'create');

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertSame(200, $response->status);
    }

    public function testValidatesTokenInQueryString(): void
    {
        $token = $this->csrfManager->generateToken();
        
        $request = new Request(
            method: 'POST',
            uri: '/test',
            query: ['_csrf_token' => $token],
            body: [],
            server: []
        );
        $request = $request
            ->withAttribute('controller', MockCsrfController::class)
            ->withAttribute('method', 'create');

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertSame(200, $response->status);
    }

    public function testThrowsExceptionForInvalidToken(): void
    {
        $this->csrfManager->generateToken();
        
        $request = new Request(
            method: 'POST',
            uri: '/test',
            query: [],
            body: ['_csrf_token' => 'invalid-token'],
            server: []
        );
        $request = $request
            ->withAttribute('controller', MockCsrfController::class)
            ->withAttribute('method', 'create');

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Invalid or missing CSRF token');
        
        $this->middleware->process($request, $handler);
    }

    public function testThrowsExceptionForMissingToken(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/test',
            query: [],
            body: [],
            server: []
        );
        $request = $request
            ->withAttribute('controller', MockCsrfController::class)
            ->withAttribute('method', 'create');

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Invalid or missing CSRF token');
        
        $this->middleware->process($request, $handler);
    }

    public function testCustomFieldName(): void
    {
        $token = $this->csrfManager->generateToken();
        
        $request = new Request(
            method: 'POST',
            uri: '/test',
            query: [],
            body: ['custom_token' => $token],
            server: []
        );
        $request = $request
            ->withAttribute('controller', MockCustomCsrfController::class)
            ->withAttribute('method', 'create');

        $handler = $this->createMockHandler(new Response('OK', 200));
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertSame(200, $response->status);
    }

    private function createMockHandler(Response $response): RequestHandlerInterface
    {
        return new class($response) implements RequestHandlerInterface {
            public function __construct(private Response $response) {}

            public function handle(Request $request): Response
            {
                return $this->response;
            }
        };
    }
}

// Mock controller with #[Csrf] attribute
class MockCsrfController
{
    #[Csrf]
    public function create(): Response
    {
        return new Response('Created', 201);
    }
}

// Mock controller with custom CSRF field name
class MockCustomCsrfController
{
    #[Csrf(fieldName: 'custom_token')]
    public function create(): Response
    {
        return new Response('Created', 201);
    }
}
