<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\Assert\Min;
use Core\Attribute\Assert\Max;
use Core\Attribute\Assert\Range;
use PHPUnit\Framework\TestCase;

class NumericValidationTest extends TestCase
{
    public function testMinValidatesCorrectly(): void
    {
        $rule = new Min(18);

        $this->assertTrue($rule->validate(18));
        $this->assertTrue($rule->validate(25));
        $this->assertTrue($rule->validate(100));
        $this->assertFalse($rule->validate(17));
        $this->assertFalse($rule->validate(0));
        $this->assertFalse($rule->validate('invalid'));
    }

    public function testMinWithFloat(): void
    {
        $rule = new Min(5.5);

        $this->assertTrue($rule->validate(5.5));
        $this->assertTrue($rule->validate(5.6));
        $this->assertTrue($rule->validate(10));
        $this->assertFalse($rule->validate(5.4));
        $this->assertFalse($rule->validate(0));
    }

    public function testMaxValidatesCorrectly(): void
    {
        $rule = new Max(100);

        $this->assertTrue($rule->validate(100));
        $this->assertTrue($rule->validate(50));
        $this->assertTrue($rule->validate(0));
        $this->assertFalse($rule->validate(101));
        $this->assertFalse($rule->validate(200));
        $this->assertFalse($rule->validate('invalid'));
    }

    public function testMaxWithFloat(): void
    {
        $rule = new Max(10.5);

        $this->assertTrue($rule->validate(10.5));
        $this->assertTrue($rule->validate(10.4));
        $this->assertTrue($rule->validate(0));
        $this->assertFalse($rule->validate(10.6));
        $this->assertFalse($rule->validate(20));
    }

    public function testRangeValidatesCorrectly(): void
    {
        $rule = new Range(0, 100);

        $this->assertTrue($rule->validate(0));
        $this->assertTrue($rule->validate(50));
        $this->assertTrue($rule->validate(100));
        $this->assertFalse($rule->validate(-1));
        $this->assertFalse($rule->validate(101));
        $this->assertFalse($rule->validate('invalid'));
    }

    public function testRangeWithFloat(): void
    {
        $rule = new Range(5.5, 10.5);

        $this->assertTrue($rule->validate(5.5));
        $this->assertTrue($rule->validate(7.8));
        $this->assertTrue($rule->validate(10.5));
        $this->assertFalse($rule->validate(5.4));
        $this->assertFalse($rule->validate(10.6));
    }

    public function testMinMessage(): void
    {
        $rule = new Min(18);
        $message = $rule->message('age');

        $this->assertSame('The field [age] must be at least 18.', $message);
    }

    public function testMaxMessage(): void
    {
        $rule = new Max(100);
        $message = $rule->message('score');

        $this->assertSame('The field [score] must not exceed 100.', $message);
    }

    public function testRangeMessage(): void
    {
        $rule = new Range(0, 100);
        $message = $rule->message('percentage');

        $this->assertSame('The field [percentage] must be between 0 and 100.', $message);
    }
}
