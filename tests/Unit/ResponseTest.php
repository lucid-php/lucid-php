<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testTextResponse(): void
    {
        $response = Response::text('Hello World');

        $this->assertSame('Hello World', $response->content);
        $this->assertSame(200, $response->status);
        $this->assertSame('text/plain', $response->headers['Content-Type']);
    }

    public function testJsonResponse(): void
    {
        $response = Response::json(['status' => 'ok']);

        $this->assertSame('{"status":"ok"}', $response->content);
        $this->assertSame(200, $response->status);
        $this->assertSame('application/json', $response->headers['Content-Type']);
    }

    public function testJsonResponseWithStatusCode(): void
    {
        $response = Response::json(['error' => 'Not Found'], 404);

        $this->assertSame(404, $response->status);
    }

    public function testRedirectResponse(): void
    {
        $response = Response::redirect('/login');

        $this->assertSame('', $response->content);
        $this->assertSame(302, $response->status);
        $this->assertSame('/login', $response->headers['Location']);
    }

    public function testRedirectWithCustomStatus(): void
    {
        $response = Response::redirect('/admin', 301);

        $this->assertSame(301, $response->status);
        $this->assertSame('/admin', $response->headers['Location']);
    }

    public function testWithHeader(): void
    {
        $response = Response::text('Hello')
            ->withHeader('X-Custom', 'value');

        $this->assertSame('value', $response->headers['X-Custom']);
        $this->assertSame('text/plain', $response->headers['Content-Type']);
    }

    public function testWithHeaders(): void
    {
        $response = Response::json(['data' => 'test'])
            ->withHeaders([
                'X-Custom-1' => 'value1',
                'X-Custom-2' => 'value2'
            ]);

        $this->assertSame('value1', $response->headers['X-Custom-1']);
        $this->assertSame('value2', $response->headers['X-Custom-2']);
        $this->assertSame('application/json', $response->headers['Content-Type']);
    }
}
