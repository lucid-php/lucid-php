<?php

declare(strict_types=1);

/**
 * File Upload Configuration
 * 
 * Explicit upload settings - no hidden defaults.
 * All paths must be absolute.
 */
return [
    /**
     * Base directory for all file uploads.
     * Must be an absolute path and writable by the web server.
     */
    'upload_directory' => __DIR__ . '/../storage/uploads',

    /**
     * Whether to create upload directory if it doesn't exist.
     */
    'create_directory' => true,

    /**
     * Maximum file size in bytes.
     * Note: Also limited by php.ini settings (upload_max_filesize, post_max_size).
     */
    'max_file_size' => 10 * 1024 * 1024, // 10MB

    /**
     * Allowed file extensions (whitelist).
     * Extensions are case-insensitive.
     * Empty array means no restriction (not recommended).
     */
    'allowed_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp', // Images
        'pdf', // Documents
        'txt', 'csv', // Text
    ],

    /**
     * Allowed MIME types (whitelist).
     * Checked against actual file content, not client-provided type.
     * Empty array means no restriction (not recommended).
     */
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'text/csv',
    ],

    /**
     * Subdirectories for organizing uploads.
     * You can reference these in your upload code.
     */
    'subdirectories' => [
        'avatars' => 'avatars',
        'documents' => 'documents',
        'images' => 'images',
    ],
];
