<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mail Driver
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "smtp", "log", "array"
    |
    | - smtp: Sends emails via SMTP server
    | - log: Logs emails to the logger (for development)
    | - array: Stores emails in memory (for testing)
    |
    */
    'driver' => getenv('MAIL_DRIVER') ?: 'log',

    /*
    |--------------------------------------------------------------------------
    | Default From Address
    |--------------------------------------------------------------------------
    |
    | The default email address that will be used as the "from" address
    | if no from address is specified when sending an email.
    |
    */
    'from' => [
        'address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@example.com',
        'name' => getenv('MAIL_FROM_NAME') ?: 'Example App',
    ],

    /*
    |--------------------------------------------------------------------------
    | SMTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the SMTP mail driver.
    |
    | encryption: "tls", "ssl", or "" (none)
    |
    */
    'smtp' => [
        'host' => getenv('MAIL_HOST') ?: 'smtp.mailtrap.io',
        'port' => (int) (getenv('MAIL_PORT') ?: 2525),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    ],
];
