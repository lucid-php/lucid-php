<?php

declare(strict_types=1);

namespace Core\Http;

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
    ) {
    }

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
            'message' => $this->getSafeMessage($exception),
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
     * Return a message that is safe to expose to the client.
     *
     * In debug mode the real message is always shown. In production only
     * intentionally client-facing exceptions (HttpException hierarchy and
     * ValidationException) expose their message; any other (500-level)
     * exception is masked with a generic message to avoid leaking internal
     * details such as SQL errors, file paths, or stack-trace fragments.
     */
    private function getSafeMessage(Throwable $exception): string
    {
        if ($this->debug) {
            return $exception->getMessage();
        }

        if ($exception instanceof HttpException || $exception instanceof ValidationException) {
            return $exception->getMessage();
        }

        return 'An unexpected error occurred.';
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
            array_map(fn ($frame) => [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'],
            ], $trace),
            0,
            10  // Limit to 10 frames
        );
    }
}
