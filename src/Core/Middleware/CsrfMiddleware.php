<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;
use Core\Security\CsrfTokenManager;
use Core\Security\Csrf;
use Core\Http\ForbiddenException;
use ReflectionClass;
use ReflectionMethod;

/**
 * CSRF Protection Middleware
 * 
 * Validates CSRF tokens for routes marked with #[Csrf] attribute.
 * 
 * Philosophy: Explicit Over Convenient
 * - Only validates routes with explicit #[Csrf] attribute
 * - No automatic protection (reduces magic)
 * - Token can come from body, header, or query string
 * - Safe methods (GET, HEAD, OPTIONS) are never checked
 */
class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private readonly CsrfTokenManager $csrfManager
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // Skip safe HTTP methods
        if (in_array($request->method, self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        // Check if route has #[Csrf] attribute
        $csrfAttribute = $this->getCsrfAttribute($request);
        
        if ($csrfAttribute === null) {
            // No CSRF protection required
            return $handler->handle($request);
        }

        // Extract token from request
        $token = $this->extractToken($request, $csrfAttribute);

        if ($token === null || !$this->csrfManager->validateToken($token)) {
            throw new ForbiddenException('Invalid or missing CSRF token');
        }

        return $handler->handle($request);
    }

    /**
     * Get the #[Csrf] attribute from the route handler
     * 
     * @return Csrf|null
     */
    private function getCsrfAttribute(Request $request): ?Csrf
    {
        $controller = $request->getAttribute('controller');
        $method = $request->getAttribute('method');

        if ($controller === null || $method === null) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($controller);
            $methodReflection = $reflection->getMethod($method);
            
            $attributes = $methodReflection->getAttributes(Csrf::class);
            
            if (empty($attributes)) {
                return null;
            }

            return $attributes[0]->newInstance();
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Extract CSRF token from request
     * Checks: body, header, query string (in that order)
     * 
     * @return string|null
     */
    private function extractToken(Request $request, Csrf $csrf): ?string
    {
        // 1. Check request body
        if (isset($request->body[$csrf->fieldName])) {
            return $request->body[$csrf->fieldName];
        }

        // 2. Check headers
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $csrf->headerName));
        if (isset($request->server[$headerKey])) {
            return $request->server[$headerKey];
        }

        // 3. Check query string
        if (isset($request->query[$csrf->fieldName])) {
            return $request->query[$csrf->fieldName];
        }

        return null;
    }
}
