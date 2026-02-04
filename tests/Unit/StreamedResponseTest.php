<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\StreamedResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StreamedResponseTest extends TestCase
{
    private string $testDir;
    private string $testFile;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/framework_stream_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
        
        $this->testFile = $this->testDir . '/stream.txt';
        file_put_contents($this->testFile, 'Streamed content for testing');
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

    public function testConstructorValidatesFileExists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        new StreamedResponse($this->testDir . '/nonexistent.txt');
    }

    public function testConstructorValidatesFileReadable(): void
    {
        $unreadable = $this->testDir . '/unreadable.txt';
        file_put_contents($unreadable, 'test');
        chmod($unreadable, 0000);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('File not readable');

            new StreamedResponse($unreadable);
        } finally {
            chmod($unreadable, 0644);
            unlink($unreadable);
        }
    }

    public function testConstructorAcceptsValidFile(): void
    {
        $response = new StreamedResponse($this->testFile);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testConstructorWithCustomFilename(): void
    {
        $response = new StreamedResponse($this->testFile, 'download.txt');

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testConstructorWithMimeType(): void
    {
        $response = new StreamedResponse($this->testFile, mimeType: 'text/plain');

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testConstructorWithInlineMode(): void
    {
        $response = new StreamedResponse($this->testFile, inline: true);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testConstructorWithCustomChunkSize(): void
    {
        $response = new StreamedResponse($this->testFile, chunkSize: 4096);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testConstructorWithRangeSupport(): void
    {
        $response = new StreamedResponse($this->testFile, supportRanges: true);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testConstructorWithRangeDisabled(): void
    {
        $response = new StreamedResponse($this->testFile, supportRanges: false);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testSendMethodExists(): void
    {
        $response = new StreamedResponse($this->testFile);

        $this->assertTrue(method_exists($response, 'send'));
    }

    public function testHandlesLargeFiles(): void
    {
        $largeFile = $this->testDir . '/large.bin';
        $content = str_repeat('X', 1000000); // 1MB
        file_put_contents($largeFile, $content);

        $response = new StreamedResponse($largeFile, chunkSize: 8192);

        $this->assertInstanceOf(StreamedResponse::class, $response);

        unlink($largeFile);
    }

    public function testExplicitChunkSizeParameter(): void
    {
        // Small chunk size for testing
        $response = new StreamedResponse($this->testFile, chunkSize: 1024);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testAllParametersCombined(): void
    {
        $response = new StreamedResponse(
            path: $this->testFile,
            filename: 'custom.txt',
            mimeType: 'text/plain',
            inline: true,
            chunkSize: 4096,
            supportRanges: true
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }
}
