<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testCreateFromGlobalsWithJsonBody(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_GET = ['page' => '1'];

        $request = Request::createFromGlobals();

        $this->assertSame('POST', $request->method);
        $this->assertSame('/api/users', $request->uri);
        $this->assertSame(['page' => '1'], $request->query);
    }

    public function testWithAttribute(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withAttribute('user', ['id' => 1]);

        $this->assertNotSame($request, $newRequest);
        $this->assertNull($request->getAttribute('user'));
        $this->assertSame(['id' => 1], $newRequest->getAttribute('user'));
    }

    public function testGetAttributeWithDefault(): void
    {
        $request = new Request('GET', '/');
        
        $this->assertSame('default', $request->getAttribute('missing', 'default'));
        $this->assertNull($request->getAttribute('missing'));
    }
}
