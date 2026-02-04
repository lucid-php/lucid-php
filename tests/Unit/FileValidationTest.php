<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\Assert\FileRequired;
use Core\Attribute\Assert\FileMaxSize;
use Core\Attribute\Assert\FileExtension;
use Core\Attribute\Assert\FileMimeType;
use Core\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class FileValidationTest extends TestCase
{
    // FileRequired Tests
    
    public function testFileRequiredPassesForValidUpload(): void
    {
        $rule = new FileRequired();
        $file = new UploadedFile('test.jpg', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        
        $this->assertTrue($rule->validate($file));
    }

    public function testFileRequiredFailsForInvalidUpload(): void
    {
        $rule = new FileRequired();
        $file = new UploadedFile('test.jpg', '', 0, UPLOAD_ERR_NO_FILE);
        
        $this->assertFalse($rule->validate($file));
    }

    public function testFileRequiredFailsForNonUploadedFile(): void
    {
        $rule = new FileRequired();
        
        $this->assertFalse($rule->validate(null));
        $this->assertFalse($rule->validate('string'));
        $this->assertFalse($rule->validate(123));
    }

    public function testFileRequiredMessage(): void
    {
        $rule = new FileRequired();
        $this->assertSame('Field avatar requires a valid file upload', $rule->message('avatar'));
    }

    // FileMaxSize Tests

    public function testFileMaxSizePassesForSmallerFile(): void
    {
        $rule = new FileMaxSize(maxBytes: 2048);
        $file = new UploadedFile('test.jpg', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        
        $this->assertTrue($rule->validate($file));
    }

    public function testFileMaxSizePassesForExactSize(): void
    {
        $rule = new FileMaxSize(maxBytes: 1024);
        $file = new UploadedFile('test.jpg', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        
        $this->assertTrue($rule->validate($file));
    }

    public function testFileMaxSizeFailsForLargerFile(): void
    {
        $rule = new FileMaxSize(maxBytes: 1024);
        $file = new UploadedFile('test.jpg', '/tmp/php123', 2048, UPLOAD_ERR_OK);
        
        $this->assertFalse($rule->validate($file));
    }

    public function testFileMaxSizeFailsForInvalidUpload(): void
    {
        $rule = new FileMaxSize(maxBytes: 1024);
        $file = new UploadedFile('test.jpg', '', 0, UPLOAD_ERR_NO_FILE);
        
        $this->assertFalse($rule->validate($file));
    }

    public function testFileMaxSizeMessage(): void
    {
        $rule = new FileMaxSize(maxBytes: 5 * 1024 * 1024); // 5MB
        $this->assertSame('Field document must not exceed 5MB', $rule->message('document'));
    }

    // FileExtension Tests

    public function testFileExtensionPassesForAllowedExtension(): void
    {
        $rule = new FileExtension(allowed: ['jpg', 'png']);
        $file = new UploadedFile('test.jpg', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        
        $this->assertTrue($rule->validate($file));
    }

    public function testFileExtensionIsCaseInsensitive(): void
    {
        $rule = new FileExtension(allowed: ['jpg', 'png']);
        $file = new UploadedFile('test.JPG', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        
        $this->assertTrue($rule->validate($file));
    }

    public function testFileExtensionFailsForDisallowedExtension(): void
    {
        $rule = new FileExtension(allowed: ['jpg', 'png']);
        $file = new UploadedFile('test.pdf', '/tmp/php123', 1024, UPLOAD_ERR_OK);
        
        $this->assertFalse($rule->validate($file));
    }

    public function testFileExtensionFailsForInvalidUpload(): void
    {
        $rule = new FileExtension(allowed: ['jpg', 'png']);
        $file = new UploadedFile('test.jpg', '', 0, UPLOAD_ERR_NO_FILE);
        
        $this->assertFalse($rule->validate($file));
    }

    public function testFileExtensionMessage(): void
    {
        $rule = new FileExtension(allowed: ['jpg', 'png', 'gif']);
        $this->assertSame('Field image must be one of: jpg, png, gif', $rule->message('image'));
    }

    // FileMimeType Tests
    
    public function testFileMimeTypeFailsForNonUploadedFile(): void
    {
        $rule = new FileMimeType(allowed: ['image/jpeg']);
        
        $this->assertFalse($rule->validate(null));
        $this->assertFalse($rule->validate('string'));
    }

    public function testFileMimeTypeFailsForInvalidUpload(): void
    {
        $rule = new FileMimeType(allowed: ['image/jpeg']);
        $file = new UploadedFile('test.jpg', '', 0, UPLOAD_ERR_NO_FILE);
        
        $this->assertFalse($rule->validate($file));
    }

    public function testFileMimeTypeMessage(): void
    {
        $rule = new FileMimeType(allowed: ['image/jpeg', 'image/png']);
        $this->assertSame('Field photo must be one of: image/jpeg, image/png', $rule->message('photo'));
    }
}
