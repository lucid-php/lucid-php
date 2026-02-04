<?php

declare(strict_types=1);

namespace Core\Http\Client;

interface HttpClientInterface
{
    public function send(HttpRequest $request): HttpResponse;
    
    public function get(string $url, array $headers = []): HttpResponse;
    
    public function post(string $url, mixed $body = null, array $headers = []): HttpResponse;
    
    public function put(string $url, mixed $body = null, array $headers = []): HttpResponse;
    
    public function patch(string $url, mixed $body = null, array $headers = []): HttpResponse;
    
    public function delete(string $url, array $headers = []): HttpResponse;
}
