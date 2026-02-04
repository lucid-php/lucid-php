<?php

declare(strict_types=1);

namespace Core\Http\Client;

class CurlHttpClient implements HttpClientInterface
{
    public function __construct(
        private int $defaultTimeout = 30,
        private bool $defaultVerifySSL = true,
    ) {
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $startTime = microtime(true);
        
        $ch = curl_init($request->url);
        
        if ($ch === false) {
            throw HttpClientException::connectionFailed($request, 'Failed to initialize cURL');
        }

        try {
            $this->configureCurl($ch, $request);
            
            $responseBody = curl_exec($ch);
            
            if ($responseBody === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                
                if ($errno === CURLE_OPERATION_TIMEOUTED) {
                    throw HttpClientException::timeout($request);
                }
                
                throw HttpClientException::fromCurl($error, $request);
            }
            
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            
            $headerString = substr($responseBody, 0, $headerSize);
            $body = substr($responseBody, $headerSize);
            
            $headers = $this->parseHeaders($headerString);
            $duration = microtime(true) - $startTime;
            
            return new HttpResponse($statusCode, $body, $headers, $duration);
            
        } finally {
            // curl_close() is deprecated in PHP 8.5 (no-op since PHP 8.0)
            // Resources are automatically freed
        }
    }

    public function get(string $url, array $headers = []): HttpResponse
    {
        return $this->send(HttpRequest::get($url, $headers));
    }

    public function post(string $url, mixed $body = null, array $headers = []): HttpResponse
    {
        return $this->send(HttpRequest::post($url, $body, $headers));
    }

    public function put(string $url, mixed $body = null, array $headers = []): HttpResponse
    {
        return $this->send(HttpRequest::put($url, $body, $headers));
    }

    public function patch(string $url, mixed $body = null, array $headers = []): HttpResponse
    {
        return $this->send(HttpRequest::patch($url, $body, $headers));
    }

    public function delete(string $url, array $headers = []): HttpResponse
    {
        return $this->send(HttpRequest::delete($url, $headers));
    }

    private function configureCurl($ch, HttpRequest $request): void
    {
        // Return headers in response
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Timeout
        $timeout = $request->timeout > 0 ? $request->timeout : $this->defaultTimeout;
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        
        // SSL Verification
        $verifySSL = $request->verifySSL ?? $this->defaultVerifySSL;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySSL ? 2 : 0);
        
        // Method
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method);
        
        // Body
        if ($request->body !== null) {
            $body = $this->prepareBody($request->body, $request->headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        // Headers
        if (!empty($request->headers)) {
            $headers = [];
            foreach ($request->headers as $name => $value) {
                $headers[] = "{$name}: {$value}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // Follow redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    }

    private function prepareBody(mixed $body, array $headers): string
    {
        // Check if Content-Type is application/json
        $isJson = false;
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Content-Type') === 0 && 
                str_contains(strtolower($value), 'application/json')) {
                $isJson = true;
                break;
            }
        }
        
        if ($isJson && (is_array($body) || is_object($body))) {
            return json_encode($body);
        }
        
        if (is_string($body)) {
            return $body;
        }
        
        if (is_array($body)) {
            return http_build_query($body);
        }
        
        return (string) $body;
    }

    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);
        
        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }
        
        return $headers;
    }
}
