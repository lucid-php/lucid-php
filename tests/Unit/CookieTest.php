<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Cookie;
use Core\Http\Request;
use Core\Http\Response;
use PHPUnit\Framework\TestCase;

class CookieTest extends TestCase
{
    public function testSecureDefaults(): void
    {
        $cookie = new Cookie('session', 'abc123');

        $this->assertTrue($cookie->httpOnly);
        $this->assertTrue($cookie->secure);
        $this->assertSame('Lax', $cookie->sameSite);
        $this->assertSame('/', $cookie->path);
        $this->assertSame(0, $cookie->expires);
    }

    public function testOptionsArrayMatchesSetcookieShape(): void
    {
        $cookie = new Cookie('t', 'v', expires: 123, path: '/app', domain: 'example.com', sameSite: 'Strict');

        $this->assertSame([
            'expires' => 123,
            'path' => '/app',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ], $cookie->options());
    }

    public function testDeleteHelperExpiresCookie(): void
    {
        $cookie = Cookie::delete('session');

        $this->assertSame('', $cookie->value);
        $this->assertSame(1, $cookie->expires);
    }

    public function testRejectsInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('bad name');
    }

    public function testRejectsHeaderInjectionInValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('ok', "value\r\nSet-Cookie: evil=1");
    }

    public function testRejectsInvalidSameSite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('ok', 'v', sameSite: 'Nope');
    }

    public function testSameSiteNoneRequiresSecure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('ok', 'v', secure: false, sameSite: 'None');
    }

    public function testResponseWithCookieIsImmutableAndPreservesHeaders(): void
    {
        $response = Response::json(['ok' => true])->withCookie(new Cookie('a', '1'));

        $this->assertCount(1, $response->cookies);
        $this->assertInstanceOf(Cookie::class, $response->cookies[0]);
        // Header from the json() factory is preserved alongside the cookie.
        $this->assertSame('application/json', $response->headers['Content-Type']);

        // withHeader after withCookie keeps the cookie.
        $next = $response->withHeader('X-Test', 'yes');
        $this->assertCount(1, $next->cookies);
        $this->assertSame('yes', $next->headers['X-Test']);
    }

    public function testRequestReadsCookies(): void
    {
        $request = new Request('GET', '/', cookies: ['theme' => 'dark']);

        $this->assertSame('dark', $request->getCookie('theme'));
        $this->assertNull($request->getCookie('missing'));
        $this->assertSame('fallback', $request->getCookie('missing', 'fallback'));
    }
}
