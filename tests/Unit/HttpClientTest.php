<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Client\HttpRequest;
use Core\Http\Client\HttpResponse;
use Core\Http\Client\MockHttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    public function test_http_request_get_factory(): void
    {
        $request = HttpRequest::get('https://api.example.com/users');

        $this->assertSame('GET', $request->method);
        $this->assertSame('https://api.example.com/users', $request->url);
        $this->assertNull($request->body);
    }

    public function test_http_request_post_factory(): void
    {
        $request = HttpRequest::post('https://api.example.com/users', ['name' => 'John']);

        $this->assertSame('POST', $request->method);
        $this->assertSame('https://api.example.com/users', $request->url);
        $this->assertSame(['name' => 'John'], $request->body);
    }

    public function test_http_request_is_immutable(): void
    {
        $original = HttpRequest::get('https://api.example.com');
        $modified = $original->withTimeout(60);

        $this->assertSame(30, $original->timeout);
        $this->assertSame(60, $modified->timeout);
        $this->assertNotSame($original, $modified);
    }

    public function test_http_request_with_headers(): void
    {
        $request = HttpRequest::get('https://api.example.com')
            ->withHeader('X-Api-Key', 'secret')
            ->withHeader('Accept', 'application/json');

        $this->assertSame('secret', $request->headers['X-Api-Key']);
        $this->assertSame('application/json', $request->headers['Accept']);
    }

    public function test_http_request_with_bearer_token(): void
    {
        $request = HttpRequest::get('https://api.example.com')
            ->withBearerToken('my-token');

        $this->assertSame('Bearer my-token', $request->headers['Authorization']);
    }

    public function test_http_request_with_basic_auth(): void
    {
        $request = HttpRequest::get('https://api.example.com')
            ->withBasicAuth('user', 'pass');

        $expectedCredentials = base64_encode('user:pass');
        $this->assertSame("Basic {$expectedCredentials}", $request->headers['Authorization']);
    }

    public function test_http_request_as_json(): void
    {
        $request = HttpRequest::post('https://api.example.com', ['key' => 'value'])
            ->asJson();

        $this->assertSame('application/json', $request->headers['Content-Type']);
    }

    public function test_http_response_is_successful(): void
    {
        $response = new HttpResponse(200, 'OK');
        $this->assertTrue($response->isSuccessful());

        $response = new HttpResponse(404, 'Not Found');
        $this->assertFalse($response->isSuccessful());
    }

    public function test_http_response_is_client_error(): void
    {
        $response = new HttpResponse(404, 'Not Found');
        $this->assertTrue($response->isClientError());

        $response = new HttpResponse(200, 'OK');
        $this->assertFalse($response->isClientError());
    }

    public function test_http_response_is_server_error(): void
    {
        $response = new HttpResponse(500, 'Internal Server Error');
        $this->assertTrue($response->isServerError());

        $response = new HttpResponse(200, 'OK');
        $this->assertFalse($response->isServerError());
    }

    public function test_http_response_json_decode(): void
    {
        $response = new HttpResponse(200, '{"name":"John","age":30}');
        $data = $response->json();

        $this->assertSame('John', $data['name']);
        $this->assertSame(30, $data['age']);
    }

    public function test_http_response_json_decode_throws_on_invalid_json(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to decode JSON');

        $response = new HttpResponse(200, 'not valid json');
        $response->json();
    }

    public function test_http_response_header(): void
    {
        $response = new HttpResponse(
            200,
            'OK',
            ['Content-Type' => 'application/json', 'X-Custom' => 'value']
        );

        $this->assertSame('application/json', $response->header('Content-Type'));
        $this->assertSame('value', $response->header('X-Custom'));
        $this->assertNull($response->header('Non-Existent'));
    }

    public function test_http_response_header_is_case_insensitive(): void
    {
        $response = new HttpResponse(200, 'OK', ['Content-Type' => 'application/json']);

        $this->assertSame('application/json', $response->header('content-type'));
        $this->assertSame('application/json', $response->header('CONTENT-TYPE'));
    }

    public function test_http_response_has_header(): void
    {
        $response = new HttpResponse(200, 'OK', ['Content-Type' => 'application/json']);

        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertFalse($response->hasHeader('X-Custom'));
    }

    public function test_mock_client_returns_queued_responses(): void
    {
        $client = new MockHttpClient();
        
        $client->queueResponse(new HttpResponse(200, '{"id":1}'));
        $client->queueResponse(new HttpResponse(201, '{"id":2}'));

        $response1 = $client->get('https://api.example.com/users/1');
        $response2 = $client->get('https://api.example.com/users/2');

        $this->assertSame(200, $response1->statusCode);
        $this->assertSame(201, $response2->statusCode);
    }

    public function test_mock_client_records_requests(): void
    {
        $client = new MockHttpClient();
        $client->queueResponse(new HttpResponse(200, 'OK'));

        $client->get('https://api.example.com/users');
        $client->post('https://api.example.com/users', ['name' => 'John']);

        $this->assertSame(2, $client->count());
        
        $requests = $client->getRequests();
        $this->assertSame('GET', $requests[0]->method);
        $this->assertSame('POST', $requests[1]->method);
    }

    public function test_mock_client_get_last_request(): void
    {
        $client = new MockHttpClient();
        $client->queueResponse(new HttpResponse(200, 'OK'));

        $client->get('https://api.example.com/users');
        $client->post('https://api.example.com/users', ['name' => 'John']);

        $lastRequest = $client->getLastRequest();
        $this->assertNotNull($lastRequest);
        $this->assertSame('POST', $lastRequest->method);
        $this->assertSame(['name' => 'John'], $lastRequest->body);
    }

    public function test_mock_client_assert_sent(): void
    {
        $client = new MockHttpClient();
        $client->queueResponse(new HttpResponse(200, 'OK'));

        $client->post('https://api.example.com/webhook', ['event' => 'user.created']);

        $wasSent = $client->assertSent(function (HttpRequest $request) {
            return $request->method === 'POST' 
                && str_contains($request->url, 'webhook')
                && $request->body['event'] === 'user.created';
        });

        $this->assertTrue($wasSent);
    }

    public function test_mock_client_assert_sent_count(): void
    {
        $client = new MockHttpClient();
        $client->queueResponses([
            new HttpResponse(200, 'OK'),
            new HttpResponse(200, 'OK'),
            new HttpResponse(200, 'OK'),
        ]);

        $client->get('https://api.example.com/users/1');
        $client->get('https://api.example.com/users/2');
        $client->get('https://api.example.com/users/3');

        $this->assertTrue($client->assertSentCount(3));
        $this->assertFalse($client->assertSentCount(2));
    }

    public function test_mock_client_reset(): void
    {
        $client = new MockHttpClient();
        $client->queueResponse(new HttpResponse(200, 'OK'));
        $client->get('https://api.example.com/users');

        $this->assertSame(1, $client->count());

        $client->reset();

        $this->assertSame(0, $client->count());
        $this->assertNull($client->getLastRequest());
    }

    public function test_mock_client_returns_default_response_when_none_queued(): void
    {
        $client = new MockHttpClient();

        $response = $client->get('https://api.example.com/users');

        $this->assertSame(200, $response->statusCode);
        $this->assertSame('{}', $response->body);
    }
}
