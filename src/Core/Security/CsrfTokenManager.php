<?php

declare(strict_types=1);

namespace Core\Security;

use Core\Session\SessionInterface;

/**
 * CSRF Token Manager
 * 
 * Philosophy: Explicit Over Convenient
 * - No magic token fields or auto-validation
 * - Token generation and validation are explicit method calls
 * - No hidden behaviors
 * 
 * Usage:
 * - Call generateToken() to create a token
 * - Include token in forms or AJAX headers
 * - Call validateToken() to verify submissions
 */
class CsrfTokenManager
{
    private const TOKEN_KEY = '_csrf_token';
    private const TOKEN_LENGTH = 32; // 32 bytes = 64 hex chars

    public function __construct(
        private readonly SessionInterface $session
    ) {}

    /**
     * Generate a new CSRF token and store in session
     * 
     * @return string The generated token (64 hex chars)
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $this->session->set(self::TOKEN_KEY, $token);
        return $token;
    }

    /**
     * Get the current CSRF token from session
     * If no token exists, generates a new one
     * 
     * @return string The current token
     */
    public function getToken(): string
    {
        $token = $this->session->get(self::TOKEN_KEY);
        
        if ($token === null) {
            $token = $this->generateToken();
        }

        return $token;
    }

    /**
     * Validate a CSRF token against the session token
     * Uses timing-safe comparison to prevent timing attacks
     * 
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public function validateToken(string $token): bool
    {
        $sessionToken = $this->session->get(self::TOKEN_KEY);

        if ($sessionToken === null) {
            return false;
        }

        // Timing-safe comparison prevents timing attacks
        return hash_equals($sessionToken, $token);
    }

    /**
     * Regenerate the CSRF token
     * Call this after successful form submission or login
     * 
     * @return string The new token
     */
    public function regenerateToken(): string
    {
        return $this->generateToken();
    }

    /**
     * Clear the CSRF token from session
     */
    public function clearToken(): void
    {
        $this->session->remove(self::TOKEN_KEY);
    }
}
