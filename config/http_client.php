<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Timeout
    |--------------------------------------------------------------------------
    |
    | Default timeout in seconds for HTTP requests.
    |
    */
    'timeout' => (int) (getenv('HTTP_CLIENT_TIMEOUT') ?: 30),

    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    |
    | Whether to verify SSL certificates by default. Disable only for
    | development/testing with self-signed certificates.
    |
    */
    'verify_ssl' => (bool) (getenv('HTTP_CLIENT_VERIFY_SSL') ?? true),

    /*
    |--------------------------------------------------------------------------
    | Base URLs
    |--------------------------------------------------------------------------
    |
    | Named base URLs for common API endpoints. Use these to avoid
    | hardcoding URLs throughout your application.
    |
    */
    'base_urls' => [
        'api' => getenv('API_BASE_URL') ?: 'https://api.example.com',
        'webhook' => getenv('WEBHOOK_BASE_URL') ?: 'https://hooks.example.com',
    ],
];
