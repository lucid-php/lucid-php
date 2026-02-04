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
];
