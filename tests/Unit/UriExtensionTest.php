<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Uri\Rfc3986\Uri;

/**
 * Test PHP 8.5 URI Extension
 * 
 * These tests verify that the native URI extension provides RFC 3986 compliant
 * parsing and is a suitable replacement for parse_url().
 */
class UriExtensionTest extends TestCase
{
    public function testBasicUriParsing(): void
    {
        $uri = new Uri('https://example.com/path/to/resource');
        
        $this->assertSame('/path/to/resource', $uri->getPath());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('https', $uri->getScheme());
    }

    public function testUriWithQueryString(): void
    {
        $uri = new Uri('https://api.example.com/users?status=active&page=2');
        
        $this->assertSame('/users', $uri->getPath());
        $this->assertSame('status=active&page=2', $uri->getQuery());
    }

    public function testUriWithFragment(): void
    {
        $uri = new Uri('https://example.com/docs#section-1');
        
        $this->assertSame('/docs', $uri->getPath());
        $this->assertSame('section-1', $uri->getFragment());
    }

    public function testUriWithPort(): void
    {
        $uri = new Uri('http://localhost:8080/api/status');
        
        $this->assertSame('localhost', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/api/status', $uri->getPath());
    }

    public function testRootPath(): void
    {
        $uri = new Uri('/');
        
        $this->assertSame('/', $uri->getPath());
    }

    public function testPathOnly(): void
    {
        $uri = new Uri('/api/users/123');
        
        $this->assertSame('/api/users/123', $uri->getPath());
    }

    public function testEncodedCharacters(): void
    {
        $uri = new Uri('https://example.com/path%20with%20spaces');
        
        $this->assertSame('/path%20with%20spaces', $uri->getPath());
    }

    public function testEmptyPath(): void
    {
        $uri = new Uri('https://example.com');
        
        $this->assertSame('', $uri->getPath());
    }

    public function testComplexUri(): void
    {
        $uri = new Uri('https://user:pass@example.com:8443/api/v1/users?filter=active&sort=name#results');
        
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8443, $uri->getPort());
        $this->assertSame('/api/v1/users', $uri->getPath());
        $this->assertSame('filter=active&sort=name', $uri->getQuery());
        $this->assertSame('results', $uri->getFragment());
    }
}
