<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Http\Response;
use Core\Validation\ValidationException;
use Throwable;

/**
 * Exception Handler
 * 
 * Philosophy: Explicit exception-to-response mapping. No magic recovery.
 * Every exception type is explicitly mapped to an HTTP status code.
 * Development mode shows full details, production shows safe messages.
 */
class ExceptionHandler
{
    public function __construct(
        private readonly bool $debug = false
    ) {}

    /**
     * Convert an exception into an HTTP Response
     * 
     * Explicit mapping:
     * - ValidationException -> 422 Unprocessable Entity
     * - NotFoundException -> 404 Not Found
     * - UnauthorizedException -> 401 Unauthorized
     * - ForbiddenException -> 403 Forbidden
     * - BadRequestException -> 400 Bad Request
     * - ConflictException -> 409 Conflict
     * - All others -> 500 Internal Server Error
     */
    #[\NoDiscard]
    public function handle(Throwable $exception): Response
    {
        $statusCode = $this->getStatusCode($exception);
        $response = $this->buildResponse($exception, $statusCode);

        return Response::json($response, $statusCode);
    }

    /**
     * Map exception type to HTTP status code
     * Explicit - no convention, no discovery
     */
    private function getStatusCode(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof ValidationException => 422,
            $exception instanceof NotFoundException => 404,
            $exception instanceof UnauthorizedException => 401,
            $exception instanceof ForbiddenException => 403,
            $exception instanceof BadRequestException => 400,
            $exception instanceof ConflictException => 409,
            $exception instanceof HttpException => $exception->statusCode,
            default => 500,
        };
    }

    /**
     * Build response array
     * Debug mode: full details for development
     * Production mode: safe messages only
     */
    private function buildResponse(Throwable $exception, int $statusCode): array
    {
        $response = [
            'error' => $this->getErrorTitle($statusCode),
            'message' => $exception->getMessage(),
        ];

        // ValidationException: always include validation details
        if ($exception instanceof ValidationException) {
            $response['details'] = $exception->errors;
        }

        // Debug mode: include stack trace and exception class
        if ($this->debug) {
            $response['exception'] = get_class($exception);
            $response['file'] = $exception->getFile();
            $response['line'] = $exception->getLine();
            $response['trace'] = $this->formatTrace($exception->getTrace());
        }

        return $response;
    }

    /**
     * Get human-readable error title from status code
     */
    private function getErrorTitle(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            409 => 'Conflict',
            422 => 'Validation Failed',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }

    /**
     * Format stack trace for debug output
     * Limit depth to prevent massive responses
     */
    private function formatTrace(array $trace): array
    {
        return array_slice(
            array_map(fn($frame) => [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'],
            ], $trace),
            0,
            10  // Limit to 10 frames
        );
    }
}
