<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => 'Lucid-PHP',

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    | Supported: "local", "production"
    */
    'env' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    | When enabled, detailed error messages are shown. MUST stay false in
    | production: debug responses expose stack traces, file paths, and raw
    | exception messages (including DB errors). Enable via APP_DEBUG=1 locally.
    */
    'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */
    'url' => 'http://localhost:8000',
];
