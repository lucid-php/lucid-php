<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Queue Driver
    |--------------------------------------------------------------------------
    | 
    | Supported: "sync", "database"
    | 
    | sync: Executes jobs immediately (no queue, good for development/testing)
    | database: Stores jobs in database, requires queue worker to process
    */
    'driver' => 'sync',

    /*
    |--------------------------------------------------------------------------
    | Default Queue Name
    |--------------------------------------------------------------------------
    */
    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Queue Names
    |--------------------------------------------------------------------------
    | 
    | Define your queue names here for organization
    */
    'queues' => [
        'default',
        'emails',
        'notifications',
        'processing',
    ],
];
