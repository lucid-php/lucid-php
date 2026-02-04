<?php

declare(strict_types=1);

/**
 * Cache Configuration
 * 
 * Configure cache behavior for the application.
 * 
 * Available Drivers:
 * - 'file': Persistent file-based cache (production)
 * - 'array': In-memory cache (testing/development)
 * 
 * Philosophy:
 * - Explicit configuration over conventions
 * - Environment variables for deployment flexibility
 * - Sensible defaults for development
 */

return [
    /**
     * Default cache driver
     * 
     * Options: 'file', 'array'
     */
    'default' => env('CACHE_DRIVER', 'file'),

    /**
     * Default TTL (Time To Live) in seconds
     * 
     * Used when no TTL is specified explicitly
     */
    'default_ttl' => (int) env('CACHE_DEFAULT_TTL', 3600), // 1 hour

    /**
     * Driver-specific configuration
     */
    'drivers' => [
        'file' => [
            /**
             * Directory path for cache files
             * Must be writable by the application
             */
            'path' => env('CACHE_FILE_PATH', __DIR__ . '/../storage/cache'),
        ],

        'array' => [
            /**
             * No configuration needed for array cache
             * Exists in memory only
             */
        ],
    ],

    /**
     * Cache key prefix
     * 
     * Useful for namespacing caches in shared environments
     * or preventing key collisions across applications
     */
    'prefix' => env('CACHE_PREFIX', 'app_cache'),

    /**
     * Named cache stores for different use cases
     * 
     * Example: Separate caches for HTTP responses, database queries, etc.
     */
    'stores' => [
        'http' => [
            'driver' => env('CACHE_HTTP_DRIVER', 'file'),
            'ttl' => (int) env('CACHE_HTTP_TTL', 300), // 5 minutes
        ],

        'database' => [
            'driver' => env('CACHE_DB_DRIVER', 'file'),
            'ttl' => (int) env('CACHE_DB_TTL', 600), // 10 minutes
        ],

        'sessions' => [
            'driver' => env('CACHE_SESSION_DRIVER', 'file'),
            'ttl' => (int) env('CACHE_SESSION_TTL', 7200), // 2 hours
        ],
    ],
];
