<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\RateLimit;
use Core\Config\Config;
use Core\Http\TooManyRequestsException;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;
use Core\Middleware\RateLimitMiddleware;
use Core\RateLimit\InMemoryRateLimitStore;
use PHPUnit\Framework\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    private InMemoryRateLimitStore $store;
    private string $configPath;

    protected function setUp(): void
    {
        $this->store = new InMemoryRateLimitStore();
        $this->configPath = __DIR__ . '/../../config';
    }

    protected function tearDown(): void
    {
        $this->store->clear();
    }

    public function test_allows_requests_within_limit(): void
    {
        $config = new Config($this->configPath);
        $middleware = new RateLimitMiddleware($this->store, $config);

        // Create request with rate limit attribute
        $request = $this->createRequestWithRateLimit(5, 60);
        $handler = $this->createMockHandler(new Response('OK', 200));

        // First request should succeed
        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('5', $response->headers['X-RateLimit-Limit']);
        $this->assertEquals('4', $response->headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('X-RateLimit-Reset', $response->headers);
    }

    public function test_blocks_requests_exceeding_limit(): void
    {
        $config = new Config($this->configPath);
        $middleware = new RateLimitMiddleware($this->store, $config);

        $request = $this->createRequestWithRateLimit(3, 60);
        $handler = $this->createMockHandler(new Response('OK', 200));

        // Make 3 requests (at the limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $middleware->process($request, $handler);
            $this->assertEquals(200, $response->status);
        }

        // 4th request should throw exception
        $this->expectException(TooManyRequestsException::class);
        $middleware->process($request, $handler);
    }

    public function test_rate_limit_headers_decrement(): void
    {
        $config = new Config($this->configPath);
        $middleware = new RateLimitMiddleware($this->store, $config);

        $request = $this->createRequestWithRateLimit(5, 60);
        $handler = $this->createMockHandler(new Response('OK', 200));

        // First request: 4 remaining
        $response = $middleware->process($request, $handler);
        $this->assertEquals('4', $response->headers['X-RateLimit-Remaining']);

        // Second request: 3 remaining
        $response = $middleware->process($request, $handler);
        $this->assertEquals('3', $response->headers['X-RateLimit-Remaining']);

        // Third request: 2 remaining
        $response = $middleware->process($request, $handler);
        $this->assertEquals('2', $response->headers['X-RateLimit-Remaining']);
    }

    public function test_skips_routes_without_rate_limit_attribute(): void
    {
        $config = new Config($this->configPath);
        $middleware = new RateLimitMiddleware($this->store, $config);

        // Request without rate limit attribute
        $request = new Request(
            method: 'GET',
            uri: '/api/status',
            server: ['REMOTE_ADDR' => '127.0.0.1']
        );
        $handler = $this->createMockHandler(new Response('OK', 200));

        $response = $middleware->process($request, $handler);

        // No rate limit headers should be present
        $this->assertArrayNotHasKey('X-RateLimit-Limit', $response->headers);
        $this->assertArrayNotHasKey('X-RateLimit-Remaining', $response->headers);
    }

    public function test_different_ips_have_separate_limits(): void
    {
        $config = new Config($this->configPath);
        $middleware = new RateLimitMiddleware($this->store, $config);

        $handler = $this->createMockHandler(new Response('OK', 200));

        // IP 1 makes 2 requests
        $request1 = $this->createRequestWithRateLimit(3, 60, '192.168.1.1');
        $middleware->process($request1, $handler);
        $response = $middleware->process($request1, $handler);
        $this->assertEquals('1', $response->headers['X-RateLimit-Remaining']);

        // IP 2 should have fresh limit
        $request2 = $this->createRequestWithRateLimit(3, 60, '192.168.1.2');
        $response = $middleware->process($request2, $handler);
        $this->assertEquals('2', $response->headers['X-RateLimit-Remaining']);
    }

    public function test_different_routes_have_separate_limits(): void
    {
        $config = new Config($this->configPath);
        $middleware = new RateLimitMiddleware($this->store, $config);

        $handler = $this->createMockHandler(new Response('OK', 200));

        // Route 1 makes 2 requests
        $request1 = $this->createRequestWithRateLimit(3, 60, '127.0.0.1', '/api/users');
        $middleware->process($request1, $handler);
        $response = $middleware->process($request1, $handler);
        $this->assertEquals('1', $response->headers['X-RateLimit-Remaining']);

        // Route 2 should have fresh limit
        $request2 = $this->createRequestWithRateLimit(3, 60, '127.0.0.1', '/api/posts');
        $response = $middleware->process($request2, $handler);
        $this->assertEquals('2', $response->headers['X-RateLimit-Remaining']);
    }

    public function test_rate_limit_resets_after_window(): void
    {
        $config = new Config($this->configPath);
        $middleware = new RateLimitMiddleware($this->store, $config);

        // Use 1-second window for testing
        $request = $this->createRequestWithRateLimit(2, 1);
        $handler = $this->createMockHandler(new Response('OK', 200));

        // Make 2 requests (at limit)
        $middleware->process($request, $handler);
        $response = $middleware->process($request, $handler);
        $this->assertEquals('0', $response->headers['X-RateLimit-Remaining']);

        // Wait for window to expire
        sleep(2);

        // Should be able to make requests again
        $response = $middleware->process($request, $handler);
        $this->assertEquals(200, $response->status);
        $this->assertEquals('1', $response->headers['X-RateLimit-Remaining']);
    }

    public function test_disabled_globally_skips_all_rate_limits(): void
    {
        // Create temp config with disabled=false
        $tempConfig = $this->createTempDisabledConfig();
        $config = new Config($tempConfig);
        $middleware = new RateLimitMiddleware($this->store, $config);

        $request = $this->createRequestWithRateLimit(1, 60);
        $handler = $this->createMockHandler(new Response('OK', 200));

        // Should be able to make unlimited requests
        for ($i = 0; $i < 10; $i++) {
            $response = $middleware->process($request, $handler);
            $this->assertEquals(200, $response->status);
        }

        // Cleanup
        unlink($tempConfig . '/ratelimit.php');
        rmdir($tempConfig);
    }

    public function test_too_many_requests_exception_has_reset_time(): void
    {
        $resetTime = time() + 60;
        $exception = new TooManyRequestsException('Rate limit exceeded', $resetTime);

        $this->assertEquals(429, $exception->statusCode);
        $this->assertEquals($resetTime, $exception->getResetTime());
        $this->assertArrayHasKey('Retry-After', $exception->headers);
    }

    private function createRequestWithRateLimit(
        int $requests,
        int $window,
        string $ip = '127.0.0.1',
        string $uri = '/api/users'
    ): Request {
        // Create a mock controller class with RateLimit attribute
        $controller = new class {
            #[RateLimit(requests: 5, window: 60)]
            public function testMethod(): void {}
        };

        // Override the attribute for this specific test
        $request = new Request(
            method: 'GET',
            uri: $uri,
            server: ['REMOTE_ADDR' => $ip]
        );

        // Add controller and method info (normally set by Router)
        $request = $request
            ->withAttribute('_controller', get_class($controller))
            ->withAttribute('_method', 'testMethod');

        // Hack: Store the rate limit in request for the mock
        // In real usage, RateLimitMiddleware reads it from reflection
        $request = $request->withAttribute('_test_ratelimit', new RateLimit($requests, $window));

        return $request;
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

    private function createTempDisabledConfig(): string
    {
        $tempDir = sys_get_temp_dir() . '/test_ratelimit_' . uniqid();
        mkdir($tempDir);

        file_put_contents($tempDir . '/ratelimit.php', <<<'PHP'
<?php
return [
    'enabled' => false,
    'identifier_header' => 'REMOTE_ADDR',
];
PHP
        );

        return $tempDir;
    }
}
