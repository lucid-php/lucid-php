<?php

declare(strict_types=1);

/**
 * CORS Configuration
 * 
 * Cross-Origin Resource Sharing settings.
 * All settings are EXPLICIT - no hidden defaults.
 * 
 * Philosophy: Explicit over convenient.
 * - Want to allow all origins? Write '*' explicitly.
 * - Want credentials? Set true explicitly.
 * - No magic, no assumptions.
 */
return [
    /**
     * Allowed Origins
     * 
     * List of origins that can access the API.
     * Use '*' to allow all origins (not recommended for production with credentials).
     * 
     * Examples:
     * - ['https://example.com']
     * - ['https://app.example.com', 'https://admin.example.com']
     * - ['*'] // Allow all (use carefully)
     */
    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:5173',
    ],

    /**
     * Allowed HTTP Methods
     * 
     * Methods that are allowed for CORS requests.
     * Empty array = no methods allowed.
     */
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    /**
     * Allowed Headers
     * 
     * Headers that the client is allowed to send.
     * Common headers for APIs:
     * - Content-Type (for JSON)
     * - Authorization (for Bearer tokens)
     */
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
    ],

    /**
     * Exposed Headers
     * 
     * Headers that the browser can expose to the client.
     * Only needed if your API sends custom headers that
     * the frontend JavaScript needs to read.
     */
    'exposed_headers' => [
        'X-Total-Count',
        'X-Page-Count',
    ],

    /**
     * Allow Credentials
     * 
     * Whether to allow cookies and authorization headers.
     * 
     * IMPORTANT: If true, 'allowed_origins' CANNOT be '*'.
     * You must specify exact origins.
     */
    'allow_credentials' => false,

    /**
     * Max Age (seconds)
     * 
     * How long the browser can cache preflight requests.
     * 0 = no caching.
     * 
     * Common values:
     * - 600 (10 minutes)
     * - 3600 (1 hour)
     * - 86400 (24 hours)
     */
    'max_age' => 3600,
];
