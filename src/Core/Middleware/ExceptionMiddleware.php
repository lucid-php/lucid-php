<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\ExceptionHandler;
use Core\Http\MiddlewareInterface;
use Core\Http\RequestHandlerInterface;
use Core\Http\Request;
use Core\Http\Response;
use Throwable;

/**
 * Exception Middleware
 * 
 * Philosophy: Explicit exception catching at the top of the middleware stack.
 * All exceptions are caught and converted to proper HTTP responses.
 * No silent failures, no magic recovery - just clean error responses.
 */
class ExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ExceptionHandler $handler
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            // All exceptions are explicitly handled here
            // No surprises, no hidden behavior
            return $this->handler->handle($exception);
        }
    }
}
