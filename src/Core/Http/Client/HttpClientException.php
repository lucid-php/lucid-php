<?php

declare(strict_types=1);

namespace Core\Http\Client;

class HttpClientException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?HttpRequest $request = null,
        public readonly ?HttpResponse $response = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromCurl(string $error, HttpRequest $request): self
    {
        return new self("HTTP request failed: {$error}", $request);
    }

    public static function timeout(HttpRequest $request): self
    {
        return new self("HTTP request timed out after {$request->timeout} seconds", $request);
    }

    public static function connectionFailed(HttpRequest $request, string $reason): self
    {
        return new self("Connection failed: {$reason}", $request);
    }
}
