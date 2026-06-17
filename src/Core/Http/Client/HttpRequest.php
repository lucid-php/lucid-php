<?php

declare(strict_types=1);

namespace Core\Http\Client;

readonly class HttpRequest
{
    public function __construct(
        public string $method,
        public string $url,
        public mixed $body = null,
        public array $headers = [],
        public int $timeout = 30,
        public bool $verifySSL = true,
    ) {
        // Only http/https are permitted. Without this guard a user-influenced
        // URL could reach file://, gopher://, dict:// etc. (SSRF). Validate once
        // here since this is an immutable value object.
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException(
                "Unsupported URL scheme for HTTP request: " . ($scheme ?? 'none') . ". Only http and https are allowed."
            );
        }
    }

    public static function get(string $url, array $headers = []): self
    {
        return new self('GET', $url, headers: $headers);
    }

    public static function post(string $url, mixed $body = null, array $headers = []): self
    {
        return new self('POST', $url, $body, $headers);
    }

    public static function put(string $url, mixed $body = null, array $headers = []): self
    {
        return new self('PUT', $url, $body, $headers);
    }

    public static function patch(string $url, mixed $body = null, array $headers = []): self
    {
        return new self('PATCH', $url, $body, $headers);
    }

    public static function delete(string $url, array $headers = []): self
    {
        return new self('DELETE', $url, headers: $headers);
    }

    public function withTimeout(int $timeout): self
    {
        return new self(
            $this->method,
            $this->url,
            $this->body,
            $this->headers,
            $timeout,
            $this->verifySSL
        );
    }

    public function withoutSSLVerification(): self
    {
        return new self(
            $this->method,
            $this->url,
            $this->body,
            $this->headers,
            $this->timeout,
            false
        );
    }

    public function withHeaders(array $headers): self
    {
        return new self(
            $this->method,
            $this->url,
            $this->body,
            array_merge($this->headers, $headers),
            $this->timeout,
            $this->verifySSL
        );
    }

    public function withHeader(string $name, string $value): self
    {
        return $this->withHeaders([$name => $value]);
    }

    public function withBody(mixed $body): self
    {
        return new self(
            $this->method,
            $this->url,
            $body,
            $this->headers,
            $this->timeout,
            $this->verifySSL
        );
    }

    public function asJson(): self
    {
        return $this->withHeader('Content-Type', 'application/json');
    }

    public function withBearerToken(string $token): self
    {
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function withBasicAuth(string $username, string $password): self
    {
        $credentials = base64_encode("{$username}:{$password}");
        return $this->withHeader('Authorization', "Basic {$credentials}");
    }
}
