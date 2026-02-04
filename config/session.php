<?php

declare(strict_types=1);

/**
 * Session Configuration
 * 
 * All session settings are EXPLICIT - no hidden defaults.
 * 
 * Philosophy: Explicit over convenient.
 * - Want cookies? Configure them explicitly.
 * - Want file storage? Specify the path.
 * - No magic, no assumptions.
 * 
 * These map directly to PHP's session_start() options.
 * See: https://www.php.net/manual/en/function.session-start.php
 */
return [
    /**
     * Session Cookie Name
     * 
     * The name of the session cookie.
     * Change this to avoid conflicts with other applications.
     */
    'name' => 'framework_session',

    /**
     * Cookie Lifetime (seconds)
     * 
     * 0 = Until browser closes (session cookie)
     * > 0 = Persistent cookie with specified lifetime
     */
    'cookie_lifetime' => 0,

    /**
     * Cookie Path
     * 
     * The path on the domain where the cookie is available.
     * '/' = Available across entire domain
     */
    'cookie_path' => '/',

    /**
     * Cookie Domain
     * 
     * The domain where the cookie is available.
     * '' = Current domain only
     * '.example.com' = All subdomains of example.com
     */
    'cookie_domain' => '',

    /**
     * Cookie Secure
     * 
     * Only send cookie over HTTPS.
     * Should be true in production.
     */
    'cookie_secure' => false, // Set true in production with HTTPS

    /**
     * Cookie HttpOnly
     * 
     * Make cookie inaccessible to JavaScript.
     * Prevents XSS attacks from stealing session cookies.
     */
    'cookie_httponly' => true,

    /**
     * Cookie SameSite
     * 
     * Controls when cookies are sent with cross-site requests.
     * Options: 'Lax', 'Strict', 'None'
     * 
     * - Lax: Good default (allows GET navigation)
     * - Strict: Maximum security (no cross-site cookies)
     * - None: Required for cross-site cookies (must use cookie_secure=true)
     */
    'cookie_samesite' => 'Lax',

    /**
     * Garbage Collection Probability
     * 
     * Probability that the garbage collection process will start.
     * gc_probability / gc_divisor = chance
     * 
     * 1/100 = 1% chance on each session start
     */
    'gc_probability' => 1,
    'gc_divisor' => 100,

    /**
     * Garbage Collection Max Lifetime (seconds)
     * 
     * After this time, session data will be seen as 'garbage' and cleaned up.
     * 1440 = 24 minutes (PHP default)
     */
    'gc_maxlifetime' => 1440,

    /**
     * Use Strict Mode
     * 
     * Prevents session fixation attacks by rejecting uninitialized session IDs.
     * Should always be true.
     */
    'use_strict_mode' => true,

    /**
     * Use Cookies
     * 
     * Whether to use cookies to store the session ID on the client side.
     * Should be true (alternatives like URL rewriting are insecure).
     */
    'use_cookies' => true,

    /**
     * Use Only Cookies
     * 
     * Only use cookies (no URL rewriting).
     * Should be true for security.
     */
    'use_only_cookies' => true,
];
