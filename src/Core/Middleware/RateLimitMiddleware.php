<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Attribute\RateLimit;
use Core\Config\Config;
use Core\Http\TooManyRequestsException;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;
use Core\RateLimit\RateLimitStore;
use ReflectionClass;
use ReflectionMethod;

/**
 * Rate Limit Middleware
 * 
 * Enforces rate limits declared via #[RateLimit] attribute.
 * 
 * Philosophy: Explicit over convenient.
 * - Rate limits are declared on routes, not in distant config files
 * - No magic - if there's no attribute, there's no rate limiting
 * - Clear error responses with standard headers
 * 
 * Implementation:
 * - Reads #[RateLimit] attribute from controller method
 * - Uses client IP as identifier
 * - Tracks requests in RateLimitStore
 * - Returns 429 Too Many Requests when exceeded
 * - Adds X-RateLimit-* headers to all responses
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private bool $enabled;
    private string $identifierHeader;

    public function __construct(
        private final RateLimitStore $store,
        Config $config,
    ) {
        $rateLimitConfig = $config->all('ratelimit');
        $this->enabled = $rateLimitConfig['enabled'] ?? true;
        $this->identifierHeader = $rateLimitConfig['identifier_header'] ?? 'REMOTE_ADDR';
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // If rate limiting is disabled, skip
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        // Get rate limit configuration from route attribute
        $rateLimit = $this->getRateLimitFromRequest($request);

        // If no rate limit attribute on this route, skip
        if (!$rateLimit) {
            return $handler->handle($request);
        }

        // Build unique key for this client + route
        $key = $this->buildKey($request, $rateLimit);

        // Increment counter and get current count
        $currentCount = $this->store->increment($key, $rateLimit->window);
        $resetTime = $this->store->getResetTime($key);

        // Check if limit exceeded
        if ($currentCount > $rateLimit->requests) {
            throw new TooManyRequestsException(
                message: 'Rate limit exceeded',
                resetTime: $resetTime,
            );
        }

        // Process the request
        $response = $handler->handle($request);

        // Add rate limit headers to response
        return $this->addRateLimitHeaders($response, $rateLimit, $currentCount, $resetTime);
    }

    private function getRateLimitFromRequest(Request $request): ?RateLimit
    {
        // For testing: allow direct rate limit injection
        $testRateLimit = $request->getAttribute('_test_ratelimit');
        if ($testRateLimit instanceof RateLimit) {
            return $testRateLimit;
        }

        // Get controller and method from request attributes
        // (These are set by the Router during dispatch)
        $controller = $request->getAttribute('_controller');
        $method = $request->getAttribute('_method');

        if (!$controller || !$method) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($controller);
            $methodReflection = $reflection->getMethod($method);
            $attributes = $methodReflection->getAttributes(RateLimit::class);

            if (empty($attributes)) {
                return null;
            }

            return $attributes[0]->newInstance();
        } catch (\ReflectionException) {
            return null;
        }
    }

    private function buildKey(Request $request, RateLimit $rateLimit): string
    {
        // Use client IP as identifier
        $identifier = $request->server[$this->identifierHeader] ?? 'unknown';

        // Build key: ip:route:requests:window
        // This ensures different rate limits on same route don't conflict
        return sprintf(
            'ratelimit:%s:%s:%d:%d',
            $identifier,
            $request->uri,
            $rateLimit->requests,
            $rateLimit->window
        );
    }

    private function addRateLimitHeaders(
        Response $response,
        RateLimit $rateLimit,
        int $currentCount,
        int $resetTime
    ): Response {
        $remaining = max(0, $rateLimit->requests - $currentCount);

        return $response->withHeaders([
            'X-RateLimit-Limit' => (string)$rateLimit->requests,
            'X-RateLimit-Remaining' => (string)$remaining,
            'X-RateLimit-Reset' => (string)$resetTime,
        ]);
    }
}
