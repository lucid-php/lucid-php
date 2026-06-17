<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;

/**
 * Adds baseline HTTP security response headers.
 *
 * Philosophy: explicit configuration via a typed {@see SecurityHeadersConfig}
 * DTO (no untyped array), registered globally like any other middleware. The
 * headers added are visible here — no hidden behavior.
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SecurityHeadersConfig $config
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        $headers = [
            'X-Content-Type-Options' => $this->config->contentTypeOptions,
            'X-Frame-Options' => $this->config->frameOptions,
            'Referrer-Policy' => $this->config->referrerPolicy,
        ];

        if ($this->config->contentSecurityPolicy !== null) {
            $headers['Content-Security-Policy'] = $this->config->contentSecurityPolicy;
        }

        if ($this->config->permissionsPolicy !== null) {
            $headers['Permissions-Policy'] = $this->config->permissionsPolicy;
        }

        $hsts = $this->config->hstsHeader();
        if ($hsts !== null) {
            $headers['Strict-Transport-Security'] = $hsts;
        }

        return $response->withHeaders($headers);
    }
}
