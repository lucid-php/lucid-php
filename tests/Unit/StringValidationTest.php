<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\Assert\Pattern;
use Core\Attribute\Assert\Url;
use Core\Attribute\Assert\Alpha;
use Core\Attribute\Assert\AlphaNumeric;
use PHPUnit\Framework\TestCase;

class StringValidationTest extends TestCase
{
    public function testPatternValidatesCorrectly(): void
    {
        $rule = new Pattern('/^[A-Z][a-z]+$/');

        $this->assertTrue($rule->validate('Magnus'));
        $this->assertTrue($rule->validate('John'));
        $this->assertFalse($rule->validate('magnus'));
        $this->assertFalse($rule->validate('MAGNUS'));
        $this->assertFalse($rule->validate('Magnus123'));
        $this->assertFalse($rule->validate(123));
    }

    public function testPatternWithCustomMessage(): void
    {
        $rule = new Pattern('/^\d{4}$/', 'Must be a 4-digit code');

        $this->assertTrue($rule->validate('1234'));
        $this->assertFalse($rule->validate('123'));
        
        $message = $rule->message('code');
        $this->assertSame('Must be a 4-digit code', $message);
    }

    public function testUrlValidatesCorrectly(): void
    {
        $rule = new Url();

        $this->assertTrue($rule->validate('https://example.com'));
        $this->assertTrue($rule->validate('http://localhost'));
        $this->assertTrue($rule->validate('ftp://files.example.com'));
        $this->assertFalse($rule->validate('not-a-url'));
        $this->assertFalse($rule->validate('example.com'));
        $this->assertFalse($rule->validate(123));
    }

    public function testUrlWithSchemeRestriction(): void
    {
        $rule = new Url(schemes: ['https']);

        $this->assertTrue($rule->validate('https://example.com'));
        $this->assertFalse($rule->validate('http://example.com'));
        $this->assertFalse($rule->validate('ftp://example.com'));
    }

    public function testAlphaValidatesCorrectly(): void
    {
        $rule = new Alpha();

        $this->assertTrue($rule->validate('Magnus'));
        $this->assertTrue($rule->validate('abc'));
        $this->assertTrue($rule->validate('XYZ'));
        $this->assertFalse($rule->validate('Magnus123'));
        $this->assertFalse($rule->validate('abc def'));
        $this->assertFalse($rule->validate('test@'));
        $this->assertFalse($rule->validate(123));
    }

    public function testAlphaWithSpaces(): void
    {
        $rule = new Alpha(allowSpaces: true);

        $this->assertTrue($rule->validate('Magnus Larsson'));
        $this->assertTrue($rule->validate('John Doe'));
        $this->assertFalse($rule->validate('John Doe 123'));
        $this->assertFalse($rule->validate('test@email'));
    }

    public function testAlphaNumericValidatesCorrectly(): void
    {
        $rule = new AlphaNumeric();

        $this->assertTrue($rule->validate('Magnus123'));
        $this->assertTrue($rule->validate('abc'));
        $this->assertTrue($rule->validate('123'));
        $this->assertFalse($rule->validate('abc def'));
        $this->assertFalse($rule->validate('test@'));
        $this->assertFalse($rule->validate('user-name'));
    }

    public function testAlphaNumericWithSpaces(): void
    {
        $rule = new AlphaNumeric(allowSpaces: true);

        $this->assertTrue($rule->validate('User 123'));
        $this->assertTrue($rule->validate('Room A5'));
        $this->assertFalse($rule->validate('user@123'));
        $this->assertFalse($rule->validate('test-name'));
    }

    public function testPatternMessage(): void
    {
        $rule = new Pattern('/^\d+$/');
        $message = $rule->message('code');

        $this->assertSame('The field [code] format is invalid.', $message);
    }

    public function testUrlMessage(): void
    {
        $rule = new Url();
        $message = $rule->message('website');

        $this->assertSame('The field [website] must be a valid URL.', $message);
    }

    public function testUrlMessageWithSchemes(): void
    {
        $rule = new Url(schemes: ['https', 'http']);
        $message = $rule->message('url');

        $this->assertStringContainsString('https, http', $message);
    }

    public function testAlphaMessage(): void
    {
        $rule = new Alpha();
        $message = $rule->message('name');

        $this->assertSame('The field [name] must contain only letters.', $message);
    }

    public function testAlphaMessageWithSpaces(): void
    {
        $rule = new Alpha(allowSpaces: true);
        $message = $rule->message('fullName');

        $this->assertStringContainsString('letters and spaces', $message);
    }
}
