<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * HTTP Cookie value object.
 *
 * Philosophy:
 * - Immutable, explicitly-typed value object (no array of options).
 * - Secure defaults: HttpOnly + Secure + SameSite=Lax.
 * - Validates name/value to prevent header injection, and rejects the
 *   invalid SameSite=None-without-Secure combination at construction.
 */
readonly class Cookie
{
    public function __construct(
        public string $name,
        public string $value = '',
        public int $expires = 0,            // Unix timestamp; 0 = session cookie
        public string $path = '/',
        public string $domain = '',
        public bool $secure = true,
        public bool $httpOnly = true,
        public string $sameSite = 'Lax',    // 'Lax' | 'Strict' | 'None'
    ) {
        // RFC 6265 token: no controls, spaces, or separators in the name.
        if ($name === '' || preg_match('/[=,;\s\x00-\x1F\x7F]/', $name) === 1) {
            throw new \InvalidArgumentException("Invalid cookie name: {$name}");
        }

        // Reject header-injection / delimiter characters in the value.
        if (preg_match('/[;,\r\n]/', $value) === 1) {
            throw new \InvalidArgumentException('Cookie value contains invalid characters.');
        }

        if (!in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
            throw new \InvalidArgumentException("Invalid SameSite value: {$sameSite}");
        }

        // Browsers reject SameSite=None unless the cookie is also Secure.
        if ($sameSite === 'None' && !$secure) {
            throw new \InvalidArgumentException('SameSite=None requires the Secure flag.');
        }
    }

    /**
     * Build an already-expired cookie used to delete a cookie on the client.
     */
    public static function delete(string $name, string $path = '/', string $domain = ''): self
    {
        return new self($name, '', 1, $path, $domain);
    }

    /**
     * Options array in the shape PHP's setcookie() expects.
     *
     * @return array{expires: int, path: string, domain: string, secure: bool, httponly: bool, samesite: string}
     */
    public function options(): array
    {
        return [
            'expires' => $this->expires,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ];
    }
}
