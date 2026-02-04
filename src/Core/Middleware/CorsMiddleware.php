<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Config\Config;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;

/**
 * CORS Middleware
 * 
 * Handles Cross-Origin Resource Sharing headers.
 * Configuration is explicit and loaded from config/cors.php.
 * No magic, no global helpers.
 */
class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;
    private array $exposedHeaders;
    private bool $allowCredentials;
    private int $maxAge;

    public function __construct(private final Config $config)
    {
        // Explicit configuration loading - no defaults, no magic
        $corsConfig = $this->config->all('cors');
        
        $this->allowedOrigins = $corsConfig['allowed_origins'] ?? [];
        $this->allowedMethods = $corsConfig['allowed_methods'] ?? [];
        $this->allowedHeaders = $corsConfig['allowed_headers'] ?? [];
        $this->exposedHeaders = $corsConfig['exposed_headers'] ?? [];
        $this->allowCredentials = $corsConfig['allow_credentials'] ?? false;
        $this->maxAge = $corsConfig['max_age'] ?? 0;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // HTTP headers are in $_SERVER with HTTP_ prefix, or use the server array
        $origin = $request->server['HTTP_ORIGIN'] ?? null;

        // Handle preflight OPTIONS request
        if ($request->method === 'OPTIONS') {
            return $this->handlePreflight($origin);
        }

        // Process the actual request
        $response = $handler->handle($request);

        // Add CORS headers to response
        return $this->addCorsHeaders($response, $origin);
    }

    private function handlePreflight(?string $origin): Response
    {
        $response = new Response('', 204);

        if ($origin && $this->isOriginAllowed($origin)) {
            $headers = ['Access-Control-Allow-Origin' => $origin];
            
            if ($this->allowCredentials) {
                $headers['Access-Control-Allow-Credentials'] = 'true';
            }

            if (!empty($this->allowedMethods)) {
                $headers['Access-Control-Allow-Methods'] = implode(', ', $this->allowedMethods);
            }

            if (!empty($this->allowedHeaders)) {
                $headers['Access-Control-Allow-Headers'] = implode(', ', $this->allowedHeaders);
            }

            if ($this->maxAge > 0) {
                $headers['Access-Control-Max-Age'] = (string)$this->maxAge;
            }

            $response = $response->withHeaders($headers);
        }

        return $response;
    }

    private function addCorsHeaders(Response $response, ?string $origin): Response
    {
        if ($origin && $this->isOriginAllowed($origin)) {
            $headers = ['Access-Control-Allow-Origin' => $origin];

            if ($this->allowCredentials) {
                $headers['Access-Control-Allow-Credentials'] = 'true';
            }

            if (!empty($this->exposedHeaders)) {
                $headers['Access-Control-Expose-Headers'] = implode(', ', $this->exposedHeaders);
            }

            $response = $response->withHeaders($headers);
        }

        return $response;
    }

    private function isOriginAllowed(string $origin): bool
    {
        // Wildcard: allow all origins (explicit, not hidden)
        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        // Exact match
        return in_array($origin, $this->allowedOrigins, true);
    }
}
