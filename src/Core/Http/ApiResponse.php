<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * Standardized API Response Structure
 * 
 * Provides explicit, typed API responses following the framework's
 * zero-magic philosophy. All responses have a consistent structure.
 * 
 * Structure:
 * {
 *   "success": bool,
 *   "data": mixed,
 *   "message": string|null,
 *   "errors": array|null,
 *   "meta": array|null
 * }
 */
readonly class ApiResponse
{
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $message = null,
        public ?array $errors = null,
        public ?array $meta = null,
        public int $statusCode = 200
    ) {}

    /**
     * Create a successful response
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        ?array $meta = null,
        int $statusCode = 200
    ): self {
        return new self(
            success: true,
            data: $data,
            message: $message,
            meta: $meta,
            statusCode: $statusCode
        );
    }

    /**
     * Create an error response
     */
    public static function error(
        string $message,
        ?array $errors = null,
        int $statusCode = 400
    ): self {
        return new self(
            success: false,
            message: $message,
            errors: $errors,
            statusCode: $statusCode
        );
    }

    /**
     * Create a validation error response
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): self {
        return new self(
            success: false,
            message: $message,
            errors: $errors,
            statusCode: 422
        );
    }

    /**
     * Create a not found response
     */
    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self(
            success: false,
            message: $message,
            statusCode: 404
        );
    }

    /**
     * Create an unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(
            success: false,
            message: $message,
            statusCode: 401
        );
    }

    /**
     * Create a forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(
            success: false,
            message: $message,
            statusCode: 403
        );
    }

    /**
     * Create a paginated response
     */
    public static function paginated(
        array $items,
        int $total,
        int $page,
        int $perPage,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            data: $items,
            message: $message,
            meta: [
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => (int) ceil($total / $perPage),
                    'has_more' => $page * $perPage < $total
                ]
            ],
            statusCode: 200
        );
    }

    /**
     * Create a created response (201)
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): self {
        return new self(
            success: true,
            data: $data,
            message: $message,
            statusCode: 201
        );
    }

    /**
     * Create a no content response (204)
     */
    public static function noContent(): self
    {
        return new self(
            success: true,
            statusCode: 204
        );
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->message !== null) {
            $response['message'] = $this->message;
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        if ($this->meta !== null) {
            $response['meta'] = $this->meta;
        }

        return $response;
    }

    /**
     * Convert to HTTP Response
     */
    public function toResponse(): Response
    {
        if ($this->statusCode === 204) {
            return new Response('', 204);
        }

        return Response::json($this->toArray(), $this->statusCode);
    }
}
