<?php

declare(strict_types=1);

/**
 * Rate Limiting Configuration
 * 
 * Controls global rate limiting behavior.
 * Per-route limits are declared via #[RateLimit] attribute.
 * 
 * Philosophy: Explicit over convenient.
 * - Want to disable rate limiting globally? Set enabled = false.
 * - Want to use custom identifier? Change identifier_header.
 * - No hidden defaults, no magic.
 */
return [
    /**
     * Enable/Disable Rate Limiting Globally
     * 
     * When false, all #[RateLimit] attributes are ignored.
     * Useful for development or specific environments.
     * 
     * Default: true
     */
    'enabled' => true,

    /**
     * Identifier Header
     * 
     * The $_SERVER key used to identify clients.
     * Common options:
     * - 'REMOTE_ADDR' (default) - Client IP address
     * - 'HTTP_X_FORWARDED_FOR' - Real IP behind proxy (use carefully!)
     * - 'HTTP_X_REAL_IP' - Real IP from reverse proxy
     * 
     * SECURITY WARNING:
     * X-Forwarded-For and X-Real-IP can be spoofed by clients.
     * Only use these if you trust your reverse proxy.
     * 
     * Default: 'REMOTE_ADDR'
     */
    'identifier_header' => 'REMOTE_ADDR',

    /**
     * Storage Backend
     * 
     * Not implemented yet - currently always uses InMemoryRateLimitStore.
     * Future: 'redis', 'database', etc.
     * 
     * Note: In-memory storage resets on server restart and
     * doesn't work across multiple servers.
     */
    // 'storage' => 'memory',
];
