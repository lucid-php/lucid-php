<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Database Driver
    |--------------------------------------------------------------------------
    | Supported: "sqlite", "mysql" (also works with MariaDB)
    */
    'driver' => 'sqlite',

    /*
    |--------------------------------------------------------------------------
    | SQLite Configuration
    |--------------------------------------------------------------------------
    | Path is relative to project root
    */
    'sqlite' => [
        'path' => 'database/database.sqlite',
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL/MariaDB Configuration
    |--------------------------------------------------------------------------
    | For Docker MariaDB: host=mariadb, port=3306, user=user, password=password
    */
    'mysql' => [
        'host' => 'mariadb',
        'port' => 3306,
        'database' => 'framework',
        'username' => 'user',
        'password' => 'password',
        'charset' => 'utf8mb4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Explicit Additional Migration Paths
    |--------------------------------------------------------------------------
    | Core migrations always run from database/migrations. Add extra explicit
    | paths here when needed (for example, module-provided migrations).
    | No auto-discovery or directory scanning is performed.
    */
    'migrations' => [
        'paths' => [
            // 'database/migrations/module-catalog',
        ],
    ],
];
