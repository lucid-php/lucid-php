<?php

declare(strict_types=1);

namespace Core\Middleware;

/**
 * Typed configuration for {@see SecurityHeadersMiddleware}.
 *
 * A readonly value object instead of an untyped config array. Defaults are
 * safe for an API/app; HSTS, CSP, and Permissions-Policy are opt-in (null =
 * header not sent) because they depend on deployment specifics (HTTPS, asset
 * origins, browser features).
 */
readonly class SecurityHeadersConfig
{
    public function __construct(
        public string $frameOptions = 'DENY',
        public string $contentTypeOptions = 'nosniff',
        public string $referrerPolicy = 'strict-origin-when-cross-origin',
        public ?string $contentSecurityPolicy = null,
        public ?string $permissionsPolicy = null,
        public ?int $hstsMaxAge = null,
        public bool $hstsIncludeSubDomains = true,
        public bool $hstsPreload = false,
    ) {
    }

    /**
     * Build the Strict-Transport-Security header value, or null if HSTS is off.
     */
    public function hstsHeader(): ?string
    {
        if ($this->hstsMaxAge === null) {
            return null;
        }

        $value = "max-age={$this->hstsMaxAge}";

        if ($this->hstsIncludeSubDomains) {
            $value .= '; includeSubDomains';
        }

        if ($this->hstsPreload) {
            $value .= '; preload';
        }

        return $value;
    }
}
