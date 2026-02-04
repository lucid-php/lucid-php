<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Response;
use Core\Http\StreamedResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ResponseFileTest extends TestCase
{
    private string $testDir;
    private string $testFile;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/framework_response_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
        
        $this->testFile = $this->testDir . '/test.txt';
        file_put_contents($this->testFile, 'Test content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    }

    public function testFileReturnsResponseWithContent(): void
    {
        $response = Response::file($this->testFile, mimeType: 'text/plain');

        $this->assertSame('Test content', $response->content);
        $this->assertSame(200, $response->status);
    }

    public function testFileIncludesCorrectHeaders(): void
    {
        $response = Response::file($this->testFile, 'download.txt', 'text/plain');

        $this->assertSame('text/plain', $response->headers['Content-Type']);
        $this->assertSame('attachment; filename="download.txt"', $response->headers['Content-Disposition']);
        $this->assertSame('12', $response->headers['Content-Length']); // "Test content" is 12 bytes
    }

    public function testFileUsesBaseNameIfNoFilenameProvided(): void
    {
        $response = Response::file($this->testFile, mimeType: 'text/plain');

        $this->assertStringContainsString('test.txt', $response->headers['Content-Disposition']);
    }

    public function testFileInlineDisplayMode(): void
    {
        $response = Response::file($this->testFile, mimeType: 'text/plain', inline: true);

        $this->assertStringStartsWith('inline;', $response->headers['Content-Disposition']);
    }

    public function testFileAttachmentMode(): void
    {
        $response = Response::file($this->testFile, mimeType: 'text/plain', inline: false);

        $this->assertStringStartsWith('attachment;', $response->headers['Content-Disposition']);
    }

    public function testFileThrowsExceptionForNonexistentFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        (void) Response::file($this->testDir . '/nonexistent.txt', mimeType: 'text/plain');
    }

    public function testFileThrowsExceptionForUnreadableFile(): void
    {
        // Create file with no read permissions
        $unreadable = $this->testDir . '/unreadable.txt';
        file_put_contents($unreadable, 'test');
        chmod($unreadable, 0000);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('File not readable');

            (void) Response::file($unreadable, mimeType: 'text/plain');
        } finally {
            chmod($unreadable, 0644);
            unlink($unreadable);
        }
    }

    public function testDownloadIsConvenienceForAttachment(): void
    {
        $response = Response::download($this->testFile, 'download.txt', 'text/plain');

        $this->assertStringStartsWith('attachment;', $response->headers['Content-Disposition']);
        $this->assertSame('text/plain', $response->headers['Content-Type']);
    }

    public function testStreamReturnsStreamedResponse(): void
    {
        $response = Response::stream($this->testFile, mimeType: 'text/plain');

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testStreamWithCustomFilename(): void
    {
        $response = Response::stream($this->testFile, 'streamed.txt', 'text/plain');

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testStreamInlineMode(): void
    {
        $response = Response::stream($this->testFile, mimeType: 'video/mp4', inline: true);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testFileWithExplicitMimeType(): void
    {
        $response = Response::file($this->testFile, mimeType: 'application/json');

        $this->assertSame('application/json', $response->headers['Content-Type']);
    }

    public function testFileDefaultsToOctetStream(): void
    {
        $response = Response::file($this->testFile);

        $this->assertSame('application/octet-stream', $response->headers['Content-Type']);
    }

    public function testFileHandlesLargeContent(): void
    {
        $largeFile = $this->testDir . '/large.txt';
        $content = str_repeat('A', 10000);
        file_put_contents($largeFile, $content);

        $response = Response::file($largeFile, mimeType: 'text/plain');

        $this->assertSame($content, $response->content);
        $this->assertSame('10000', $response->headers['Content-Length']);

        unlink($largeFile);
    }
}
