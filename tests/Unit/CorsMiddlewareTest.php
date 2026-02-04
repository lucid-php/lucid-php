<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Config\Config;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;
use Core\Middleware\CorsMiddleware;
use PHPUnit\Framework\TestCase;

class CorsMiddlewareTest extends TestCase
{
    private string $configPath;

    protected function setUp(): void
    {
        $this->configPath = __DIR__ . '/../../config';
    }

    public function test_allows_origin_from_allowed_list(): void
    {
        $config = new Config($this->configPath);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'GET',
            uri: '/api/users',
            server: ['HTTP_ORIGIN' => 'http://localhost:3000']
        );
        $handler = $this->createMockHandler(new Response('{"data": true}', 200));

        $response = $middleware->process($request, $handler);

        $this->assertEquals('http://localhost:3000', $response->headers['Access-Control-Allow-Origin']);
    }

    public function test_blocks_origin_not_in_allowed_list(): void
    {
        $config = new Config($this->configPath);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'GET',
            uri: '/api/users',
            server: ['HTTP_ORIGIN' => 'https://evil.com']
        );
        $handler = $this->createMockHandler(new Response('{"data": true}', 200));

        $response = $middleware->process($request, $handler);

        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $response->headers);
    }

    public function test_handles_preflight_options_request(): void
    {
        $config = new Config($this->configPath);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'OPTIONS',
            uri: '/api/users',
            server: ['HTTP_ORIGIN' => 'http://localhost:3000']
        );
        $handler = $this->createMockHandler(new Response('', 200));

        $response = $middleware->process($request, $handler);

        $this->assertEquals(204, $response->status);
        $this->assertEquals('http://localhost:3000', $response->headers['Access-Control-Allow-Origin']);
        $this->assertStringContainsString('GET', $response->headers['Access-Control-Allow-Methods']);
        $this->assertStringContainsString('POST', $response->headers['Access-Control-Allow-Methods']);
    }

    public function test_includes_allowed_headers_in_preflight(): void
    {
        $config = new Config($this->configPath);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'OPTIONS',
            uri: '/api/users',
            server: ['HTTP_ORIGIN' => 'http://localhost:3000']
        );
        $handler = $this->createMockHandler(new Response('', 200));

        $response = $middleware->process($request, $handler);

        $this->assertStringContainsString('Content-Type', $response->headers['Access-Control-Allow-Headers']);
        $this->assertStringContainsString('Authorization', $response->headers['Access-Control-Allow-Headers']);
    }

    public function test_includes_max_age_in_preflight(): void
    {
        $config = new Config($this->configPath);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'OPTIONS',
            uri: '/api/users',
            server: ['HTTP_ORIGIN' => 'http://localhost:3000']
        );
        $handler = $this->createMockHandler(new Response('', 200));

        $response = $middleware->process($request, $handler);

        $this->assertEquals('3600', $response->headers['Access-Control-Max-Age']);
    }

    public function test_exposes_custom_headers(): void
    {
        $config = new Config($this->configPath);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'GET',
            uri: '/api/users',
            server: ['HTTP_ORIGIN' => 'http://localhost:3000']
        );
        $handler = $this->createMockHandler(new Response('{"data": true}', 200));

        $response = $middleware->process($request, $handler);

        $this->assertStringContainsString('X-Total-Count', $response->headers['Access-Control-Expose-Headers']);
        $this->assertStringContainsString('X-Page-Count', $response->headers['Access-Control-Expose-Headers']);
    }

    public function test_handles_requests_without_origin_header(): void
    {
        $config = new Config($this->configPath);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'GET',
            uri: '/api/users',
            server: []
        );
        $handler = $this->createMockHandler(new Response('{"data": true}', 200));

        $response = $middleware->process($request, $handler);

        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $response->headers);
    }

    public function test_allows_wildcard_origin_when_configured(): void
    {
        // Create a temporary config with wildcard
        $tempConfig = $this->createTempWildcardConfig();
        $config = new Config($tempConfig);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'GET',
            uri: '/api/users',
            server: ['HTTP_ORIGIN' => 'https://any-site.com']
        );
        $handler = $this->createMockHandler(new Response('{"data": true}', 200));

        $response = $middleware->process($request, $handler);

        $this->assertEquals('https://any-site.com', $response->headers['Access-Control-Allow-Origin']);

        // Cleanup
        unlink($tempConfig . '/cors.php');
        rmdir($tempConfig);
    }

    public function test_does_not_add_credentials_header_when_disabled(): void
    {
        $config = new Config($this->configPath);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'OPTIONS',
            uri: '/api/users',
            server: ['HTTP_ORIGIN' => 'http://localhost:3000']
        );
        $handler = $this->createMockHandler(new Response('', 200));

        $response = $middleware->process($request, $handler);

        $this->assertArrayNotHasKey('Access-Control-Allow-Credentials', $response->headers);
    }

    public function test_adds_credentials_header_when_enabled(): void
    {
        // Create a temporary config with credentials enabled
        $tempConfig = $this->createTempCredentialsConfig();
        $config = new Config($tempConfig);
        $middleware = new CorsMiddleware($config);

        $request = new Request(
            method: 'OPTIONS',
            uri: '/api/users',
            server: ['HTTP_ORIGIN' => 'http://localhost:3000']
        );
        $handler = $this->createMockHandler(new Response('', 200));

        $response = $middleware->process($request, $handler);

        $this->assertEquals('true', $response->headers['Access-Control-Allow-Credentials']);

        // Cleanup
        unlink($tempConfig . '/cors.php');
        rmdir($tempConfig);
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

    private function createTempWildcardConfig(): string
    {
        $tempDir = sys_get_temp_dir() . '/test_cors_' . uniqid();
        mkdir($tempDir);

        file_put_contents($tempDir . '/cors.php', <<<'PHP'
<?php
return [
    'allowed_origins' => ['*'],
    'allowed_methods' => ['GET', 'POST'],
    'allowed_headers' => ['Content-Type'],
    'exposed_headers' => [],
    'allow_credentials' => false,
    'max_age' => 0,
];
PHP
        );

        return $tempDir;
    }

    private function createTempCredentialsConfig(): string
    {
        $tempDir = sys_get_temp_dir() . '/test_cors_cred_' . uniqid();
        mkdir($tempDir);

        file_put_contents($tempDir . '/cors.php', <<<'PHP'
<?php
return [
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_methods' => ['GET', 'POST'],
    'allowed_headers' => ['Content-Type'],
    'exposed_headers' => [],
    'allow_credentials' => true,
    'max_age' => 0,
];
PHP
        );

        return $tempDir;
    }
}
