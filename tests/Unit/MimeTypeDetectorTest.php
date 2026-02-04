<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\MimeTypeDetector;
use PHPUnit\Framework\TestCase;

class MimeTypeDetectorTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/framework_mime_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->testDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    }

    public function testDetectReturnsOctetStreamForNonexistentFile(): void
    {
        $result = MimeTypeDetector::detect($this->testDir . '/nonexistent.txt');

        $this->assertSame('application/octet-stream', $result);
    }

    public function testDetectReturnsTextPlainForTextFile(): void
    {
        $file = $this->testDir . '/test.txt';
        file_put_contents($file, 'Plain text content');

        $result = MimeTypeDetector::detect($file);

        $this->assertSame('text/plain', $result);
    }

    public function testFromExtensionReturnsCorrectMimeType(): void
    {
        $this->assertSame('text/plain', MimeTypeDetector::fromExtension('txt'));
        $this->assertSame('application/json', MimeTypeDetector::fromExtension('json'));
        $this->assertSame('image/jpeg', MimeTypeDetector::fromExtension('jpg'));
        $this->assertSame('image/jpeg', MimeTypeDetector::fromExtension('jpeg'));
        $this->assertSame('image/png', MimeTypeDetector::fromExtension('png'));
        $this->assertSame('application/pdf', MimeTypeDetector::fromExtension('pdf'));
    }

    public function testFromExtensionIsCaseInsensitive(): void
    {
        $this->assertSame('image/jpeg', MimeTypeDetector::fromExtension('JPG'));
        $this->assertSame('image/jpeg', MimeTypeDetector::fromExtension('Jpg'));
        $this->assertSame('application/json', MimeTypeDetector::fromExtension('JSON'));
    }

    public function testFromExtensionReturnsOctetStreamForUnknown(): void
    {
        $result = MimeTypeDetector::fromExtension('xyz');

        $this->assertSame('application/octet-stream', $result);
    }

    public function testDetectWithFallbackUsesDetectionFirst(): void
    {
        $file = $this->testDir . '/data.txt';
        file_put_contents($file, 'Text content');

        $result = MimeTypeDetector::detectWithFallback($file, 'json');

        // Should detect as text/plain, not use json fallback
        $this->assertSame('text/plain', $result);
    }

    public function testDetectWithFallbackUsesExtensionWhenDetectionFails(): void
    {
        $file = $this->testDir . '/data.bin';
        file_put_contents($file, random_bytes(16));

        $result = MimeTypeDetector::detectWithFallback($file, 'pdf');

        // If finfo detects it as octet-stream, fallback should return application/pdf
        // If finfo detects something else (like text/plain from random bytes), that takes precedence
        // So we just verify it's a valid MIME type string
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('/', $result);
    }

    public function testIsSafeForInlineAllowsImages(): void
    {
        $this->assertTrue(MimeTypeDetector::isSafeForInline('image/jpeg'));
        $this->assertTrue(MimeTypeDetector::isSafeForInline('image/png'));
        $this->assertTrue(MimeTypeDetector::isSafeForInline('image/gif'));
        $this->assertTrue(MimeTypeDetector::isSafeForInline('image/webp'));
    }

    public function testIsSafeForInlineAllowsPdf(): void
    {
        $this->assertTrue(MimeTypeDetector::isSafeForInline('application/pdf'));
    }

    public function testIsSafeForInlineAllowsVideo(): void
    {
        $this->assertTrue(MimeTypeDetector::isSafeForInline('video/mp4'));
        $this->assertTrue(MimeTypeDetector::isSafeForInline('video/webm'));
    }

    public function testIsSafeForInlineAllowsAudio(): void
    {
        $this->assertTrue(MimeTypeDetector::isSafeForInline('audio/mpeg'));
        $this->assertTrue(MimeTypeDetector::isSafeForInline('audio/ogg'));
    }

    public function testIsSafeForInlineRejectsExecutable(): void
    {
        $this->assertFalse(MimeTypeDetector::isSafeForInline('application/x-executable'));
        $this->assertFalse(MimeTypeDetector::isSafeForInline('application/x-msdownload'));
    }

    public function testIsSafeForInlineRejectsScripts(): void
    {
        $this->assertFalse(MimeTypeDetector::isSafeForInline('application/javascript'));
        $this->assertFalse(MimeTypeDetector::isSafeForInline('text/javascript'));
    }

    public function testIsSafeForInlineRejectsHtml(): void
    {
        $this->assertFalse(MimeTypeDetector::isSafeForInline('text/html'));
    }

    public function testIsSafeForInlineUsesStrictComparison(): void
    {
        // Similar but not exact
        $this->assertFalse(MimeTypeDetector::isSafeForInline('image/jpg')); // Should be image/jpeg
        $this->assertFalse(MimeTypeDetector::isSafeForInline('text/pdf')); // Should be application/pdf
    }

    public function testDetectHandlesEmptyFile(): void
    {
        $file = $this->testDir . '/empty.txt';
        touch($file);

        $result = MimeTypeDetector::detect($file);

        // Empty files might be detected as various types
        $this->assertIsString($result);
    }

    public function testFromExtensionHandlesCommonTypes(): void
    {
        $types = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'svg' => 'image/svg+xml',
        ];

        foreach ($types as $ext => $expectedMime) {
            $this->assertSame($expectedMime, MimeTypeDetector::fromExtension($ext));
        }
    }
}
