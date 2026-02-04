<?php

declare(strict_types=1);

/**
 * Example 2: Middleware
 * 
 * Demonstrates:
 * - Global middleware
 * - Class-level middleware
 * - Method-level middleware
 * - Custom middleware creation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Attribute\Middleware;
use Core\Attribute\Route;
use Core\Attribute\RoutePrefix;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\MiddlewareInterface;
use Core\Http\RequestHandlerInterface;

// Custom Middleware: Log all requests
class LoggingMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $startTime = microtime(true);
        
        echo "[LOG] {$request->method} {$request->uri} - Started\n";
        
        $response = $handler->handle($request);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        echo "[LOG] {$request->method} {$request->uri} - Completed in {$duration}ms\n";
        
        return $response;
    }
}

// Custom Middleware: Check API Key
class ApiKeyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $apiKey = $request->headers['X-API-Key'] ?? null;
        
        if ($apiKey !== 'secret-api-key') {
            return Response::json(['error' => 'Invalid API key'], 401);
        }
        
        return $handler->handle($request);
    }
}

// Custom Middleware: Admin Only
class AdminOnlyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $isAdmin = $request->headers['X-User-Role'] ?? null === 'admin';
        
        if (!$isAdmin) {
            return Response::json(['error' => 'Admin access required'], 403);
        }
        
        return $handler->handle($request);
    }
}

// Example: Class-level middleware applies to all methods
#[RoutePrefix('/api/secure')]
#[Middleware(ApiKeyMiddleware::class)]  // All routes require API key
class SecureController
{
    #[Route('GET', '/data')]
    public function getData(): Response
    {
        return Response::json(['data' => 'This is protected data']);
    }
    
    #[Route('GET', '/profile')]
    public function getProfile(): Response
    {
        return Response::json(['user' => 'John Doe', 'email' => 'john@example.com']);
    }
    
    // Method-level middleware - only this method requires admin
    #[Route('DELETE', '/users/:id')]
    #[Middleware(AdminOnlyMiddleware::class)]
    public function deleteUser(): Response
    {
        return Response::json(['message' => 'User deleted']);
    }
}

echo "Middleware Examples:\n";
echo "===================\n\n";

echo "1. Global Middleware (applies to ALL routes):\n";
echo "   \$router->addGlobalMiddleware(LoggingMiddleware::class);\n\n";

echo "2. Class-level Middleware (applies to all methods in the class):\n";
echo "   #[Middleware(ApiKeyMiddleware::class)]\n";
echo "   class SecureController { ... }\n\n";

echo "3. Method-level Middleware (applies to specific method):\n";
echo "   #[Route('DELETE', '/users/:id')]\n";
echo "   #[Middleware(AdminOnlyMiddleware::class)]\n";
echo "   public function deleteUser() { ... }\n\n";

echo "Middleware Execution Order:\n";
echo "- Global middlewares first\n";
echo "- Then class-level middlewares\n";
echo "- Then method-level middlewares\n";
echo "- Finally, the controller method\n\n";

echo "To test:\n";
echo "curl -H 'X-API-Key: secret-api-key' http://localhost:8000/api/secure/data\n";
echo "curl -H 'X-API-Key: secret-api-key' -H 'X-User-Role: admin' -X DELETE http://localhost:8000/api/secure/users/1\n";
