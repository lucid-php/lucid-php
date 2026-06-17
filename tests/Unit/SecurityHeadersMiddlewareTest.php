<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;
use Core\Middleware\SecurityHeadersConfig;
use Core\Middleware\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testAppliesSecureDefaultsAndPreservesResponse(): void
    {
        $middleware = new SecurityHeadersMiddleware(new SecurityHeadersConfig());
        $response = $middleware->process($this->request(), $this->handler(new Response('body', 200)));

        $this->assertSame('nosniff', $response->headers['X-Content-Type-Options']);
        $this->assertSame('DENY', $response->headers['X-Frame-Options']);
        $this->assertSame('strict-origin-when-cross-origin', $response->headers['Referrer-Policy']);
        $this->assertSame('body', $response->content);
        $this->assertSame(200, $response->status);
    }

    public function testOptionalHeadersOmittedByDefault(): void
    {
        $middleware = new SecurityHeadersMiddleware(new SecurityHeadersConfig());
        $response = $middleware->process($this->request(), $this->handler(new Response()));

        $this->assertArrayNotHasKey('Strict-Transport-Security', $response->headers);
        $this->assertArrayNotHasKey('Content-Security-Policy', $response->headers);
        $this->assertArrayNotHasKey('Permissions-Policy', $response->headers);
    }

    public function testHstsAndCspWhenConfigured(): void
    {
        $config = new SecurityHeadersConfig(
            contentSecurityPolicy: "default-src 'self'",
            hstsMaxAge: 31536000,
            hstsPreload: true,
        );
        $middleware = new SecurityHeadersMiddleware($config);
        $response = $middleware->process($this->request(), $this->handler(new Response()));

        $this->assertSame("default-src 'self'", $response->headers['Content-Security-Policy']);
        $this->assertSame('max-age=31536000; includeSubDomains; preload', $response->headers['Strict-Transport-Security']);
    }

    private function request(): Request
    {
        return new Request('GET', '/');
    }

    private function handler(Response $response): RequestHandlerInterface
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
