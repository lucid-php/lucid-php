<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Http\MiddlewareInterface;
use Core\Http\RequestHandlerInterface;
use Core\Http\Request;
use Core\Http\Response;

class LoggerMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // Pre-processing
        $start = microtime(true);
        error_log("Request: {$request->method} {$request->uri}");

        // Call next
        $response = $handler->handle($request);

        // Post-processing
        $duration = (microtime(true) - $start) * 1000;
        error_log("Response status: {$response->status} ({$duration}ms)");

        // We can add headers to the response by creating a new one (immutability)
        // Check if we need to modify the response (Response is read-only)
        // Since Response uses private(set), we probably need 'withHeader' method if we want to modify it.
        // For now, we just return it.
        
        return $response;
    }
}
