<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class UploadedFileTest extends TestCase
{
    public function testFromArrayCreatesInstance(): void
    {
        $file = UploadedFile::fromArray([
            'name' => 'test.jpg',
            'tmp_name' => '/tmp/phpABC123',
            'size' => 1024,
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/jpeg',
        ]);

        $this->assertSame('test.jpg', $file->name);
        $this->assertSame('/tmp/phpABC123', $file->tmpPath);
        $this->assertSame(1024, $file->size);
        $this->assertSame(UPLOAD_ERR_OK, $file->error);
        $this->assertSame('image/jpeg', $file->mimeType);
    }

    public function testIsValidReturnsTrueForSuccessfulUpload(): void
    {
        $file = new UploadedFile('test.jpg', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        $this->assertTrue($file->isValid());
    }

    public function testIsValidReturnsFalseForErrors(): void
    {
        $file = new UploadedFile('test.jpg', '/tmp/php123', 0, UPLOAD_ERR_NO_FILE);
        $this->assertFalse($file->isValid());
    }

    public function testGetErrorMessageReturnsCorrectMessages(): void
    {
        $tests = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
            999 => 'Unknown upload error',
        ];

        foreach ($tests as $errorCode => $expectedMessage) {
            $file = new UploadedFile('test.jpg', '/tmp/php123', 0, $errorCode);
            $this->assertSame($expectedMessage, $file->getErrorMessage());
        }
    }

    public function testGetExtensionReturnsLowercaseExtension(): void
    {
        $file = new UploadedFile('test.JPG', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        $this->assertSame('jpg', $file->getExtension());
    }

    public function testGetExtensionHandlesNoExtension(): void
    {
        $file = new UploadedFile('test', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        $this->assertSame('', $file->getExtension());
    }

    public function testGetExtensionHandlesMultipleDots(): void
    {
        $file = new UploadedFile('archive.tar.gz', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        $this->assertSame('gz', $file->getExtension());
    }
}
