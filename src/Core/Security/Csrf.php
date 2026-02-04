<?php

declare(strict_types=1);

namespace Core\Security;

use Attribute;

/**
 * CSRF Protection Attribute
 * 
 * Apply to controller methods that handle state-changing requests
 * (POST, PUT, PATCH, DELETE).
 * 
 * Philosophy: Explicit Over Convenient
 * - No automatic CSRF protection (you must add #[Csrf] explicitly)
 * - Only protect routes that need it (GET requests typically don't)
 * - Token can be in request body, header, or query string
 * 
 * Example:
 * 
 * #[Route('POST', '/users')]
 * #[Csrf]
 * public function create(CreateUserDTO $data): Response { ... }
 * 
 * Token Sources (checked in order):
 * 1. Request body: '_csrf_token' field
 * 2. Header: 'X-CSRF-Token'
 * 3. Query parameter: '_csrf_token'
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Csrf
{
    public function __construct(
        /**
         * Token field name in request body
         */
        public readonly string $fieldName = '_csrf_token',
        
        /**
         * Token header name
         */
        public readonly string $headerName = 'X-CSRF-Token'
    ) {}
}
