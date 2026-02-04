<?php

declare(strict_types=1);

namespace Core\Http\Client;

class MockHttpClient implements HttpClientInterface
{
    private array $responses = [];
    private array $requests = [];
    private int $currentIndex = 0;

    public function queueResponse(HttpResponse $response): void
    {
        $this->responses[] = $response;
    }

    public function queueResponses(array $responses): void
    {
        foreach ($responses as $response) {
            $this->queueResponse($response);
        }
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->requests[] = $request;
        
        if (empty($this->responses)) {
            // Default response if none queued
            return new HttpResponse(200, '{}', ['Content-Type' => 'application/json']);
        }
        
        $response = $this->responses[$this->currentIndex] ?? $this->responses[count($this->responses) - 1];
        
        if ($this->currentIndex < count($this->responses) - 1) {
            $this->currentIndex++;
        }
        
        return $response;
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

    /**
     * Get all requests that were sent
     * 
     * @return array<HttpRequest>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * Get the last request that was sent
     */
    public function getLastRequest(): ?HttpRequest
    {
        return end($this->requests) ?: null;
    }

    /**
     * Get count of requests sent
     */
    public function count(): int
    {
        return count($this->requests);
    }

    /**
     * Clear all recorded requests and queued responses
     */
    public function reset(): void
    {
        $this->requests = [];
        $this->responses = [];
        $this->currentIndex = 0;
    }

    /**
     * Assert that a request was sent
     */
    public function assertSent(callable $callback): bool
    {
        foreach ($this->requests as $request) {
            if ($callback($request)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Assert specific request count
     */
    public function assertSentCount(int $expectedCount): bool
    {
        return $this->count() === $expectedCount;
    }
}
