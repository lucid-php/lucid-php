<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\Assert\In;
use Core\Attribute\Assert\NotIn;
use Core\Attribute\Assert\Numeric;
use Core\Attribute\Assert\Integer;
use Core\Attribute\Assert\Boolean;
use Core\Attribute\Assert\Json;
use Core\Attribute\Assert\Uuid;
use PHPUnit\Framework\TestCase;

class TypeValidationTest extends TestCase
{
    public function testInValidatesCorrectly(): void
    {
        $rule = new In(['draft', 'published', 'archived']);

        $this->assertTrue($rule->validate('draft'));
        $this->assertTrue($rule->validate('published'));
        $this->assertTrue($rule->validate('archived'));
        $this->assertFalse($rule->validate('deleted'));
        $this->assertFalse($rule->validate(''));
    }

    public function testInWithNumericValues(): void
    {
        $rule = new In([1, 2, 3, 5, 10]);

        $this->assertTrue($rule->validate(1));
        $this->assertTrue($rule->validate(5));
        $this->assertFalse($rule->validate(4));
        $this->assertFalse($rule->validate('1')); // Strict comparison
    }

    public function testNotInValidatesCorrectly(): void
    {
        $rule = new NotIn(['admin', 'root', 'superuser']);

        $this->assertTrue($rule->validate('john'));
        $this->assertTrue($rule->validate('user123'));
        $this->assertFalse($rule->validate('admin'));
        $this->assertFalse($rule->validate('root'));
    }

    public function testNumericValidatesCorrectly(): void
    {
        $rule = new Numeric();

        $this->assertTrue($rule->validate(42));
        $this->assertTrue($rule->validate(3.14));
        $this->assertTrue($rule->validate('42'));
        $this->assertTrue($rule->validate('3.14'));
        $this->assertFalse($rule->validate('abc'));
        $this->assertFalse($rule->validate('12abc'));
    }

    public function testIntegerValidatesCorrectly(): void
    {
        $rule = new Integer();

        $this->assertTrue($rule->validate(42));
        $this->assertTrue($rule->validate('42'));
        $this->assertTrue($rule->validate('-42'));
        $this->assertFalse($rule->validate(3.14));
        $this->assertFalse($rule->validate('3.14'));
        $this->assertFalse($rule->validate('abc'));
    }

    public function testBooleanValidatesCorrectly(): void
    {
        $rule = new Boolean();

        $this->assertTrue($rule->validate(true));
        $this->assertTrue($rule->validate(false));
        $this->assertTrue($rule->validate(1));
        $this->assertTrue($rule->validate(0));
        $this->assertTrue($rule->validate('1'));
        $this->assertTrue($rule->validate('0'));
        $this->assertTrue($rule->validate('true'));
        $this->assertTrue($rule->validate('false'));
        $this->assertTrue($rule->validate('TRUE'));
        $this->assertTrue($rule->validate('FALSE'));
        $this->assertFalse($rule->validate('yes'));
        $this->assertFalse($rule->validate('no'));
        $this->assertFalse($rule->validate(2));
    }

    public function testJsonValidatesCorrectly(): void
    {
        $rule = new Json();

        $this->assertTrue($rule->validate('{"name":"John"}'));
        $this->assertTrue($rule->validate('[]'));
        $this->assertTrue($rule->validate('[1,2,3]'));
        $this->assertTrue($rule->validate('null'));
        $this->assertFalse($rule->validate('not json'));
        $this->assertFalse($rule->validate('{invalid}'));
        $this->assertFalse($rule->validate(123));
    }

    public function testUuidValidatesCorrectly(): void
    {
        $rule = new Uuid();

        $this->assertTrue($rule->validate('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertTrue($rule->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'));
        $this->assertFalse($rule->validate('not-a-uuid'));
        $this->assertFalse($rule->validate('550e8400-e29b-41d4-a716'));
        $this->assertFalse($rule->validate(123));
    }

    public function testUuidWithVersion(): void
    {
        $rule = new Uuid(version: 4);

        // Valid v4 UUID
        $this->assertTrue($rule->validate('550e8400-e29b-41d4-a716-446655440000'));
        
        // Invalid: not v4 (this is a v1 UUID)
        $this->assertFalse($rule->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'));
    }

    public function testInMessage(): void
    {
        $rule = new In(['a', 'b', 'c']);
        $message = $rule->message('status');

        $this->assertStringContainsString("'a', 'b', 'c'", $message);
    }

    public function testNumericMessage(): void
    {
        $rule = new Numeric();
        $message = $rule->message('amount');

        $this->assertSame('The field [amount] must be numeric.', $message);
    }

    public function testIntegerMessage(): void
    {
        $rule = new Integer();
        $message = $rule->message('count');

        $this->assertSame('The field [count] must be an integer.', $message);
    }

    public function testBooleanMessage(): void
    {
        $rule = new Boolean();
        $message = $rule->message('active');

        $this->assertSame('The field [active] must be a boolean value.', $message);
    }

    public function testJsonMessage(): void
    {
        $rule = new Json();
        $message = $rule->message('metadata');

        $this->assertSame('The field [metadata] must be valid JSON.', $message);
    }

    public function testUuidMessage(): void
    {
        $rule = new Uuid();
        $message = $rule->message('id');

        $this->assertSame('The field [id] must be a valid UUID.', $message);
    }

    public function testUuidMessageWithVersion(): void
    {
        $rule = new Uuid(version: 4);
        $message = $rule->message('id');

        $this->assertStringContainsString('version 4', $message);
    }
}
