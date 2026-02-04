<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test PHP 8.5 array_first() and array_last() Functions
 * 
 * These native functions provide clean array access with automatic null coalescing.
 */
class ArrayHelpersTest extends TestCase
{
    public function testArrayFirstWithNonEmptyArray(): void
    {
        $array = [1, 2, 3];
        $this->assertSame(1, array_first($array));
    }

    public function testArrayLastWithNonEmptyArray(): void
    {
        $array = [1, 2, 3];
        $this->assertSame(3, array_last($array));
    }

    public function testArrayFirstWithEmptyArray(): void
    {
        $array = [];
        $this->assertNull(array_first($array));
    }

    public function testArrayLastWithEmptyArray(): void
    {
        $array = [];
        $this->assertNull(array_last($array));
    }

    public function testArrayFirstWithAssociativeArray(): void
    {
        $array = ['name' => 'John', 'email' => 'john@example.com'];
        $this->assertSame('John', array_first($array));
    }

    public function testArrayLastWithAssociativeArray(): void
    {
        $array = ['name' => 'John', 'email' => 'john@example.com'];
        $this->assertSame('john@example.com', array_last($array));
    }

    public function testArrayFirstWithSingleElement(): void
    {
        $array = ['only'];
        $this->assertSame('only', array_first($array));
        $this->assertSame('only', array_last($array));
    }

    public function testArrayFirstPreservesNullValues(): void
    {
        $array = [null, 'second', 'third'];
        $this->assertNull(array_first($array));
    }

    public function testArrayLastPreservesNullValues(): void
    {
        $array = ['first', 'second', null];
        $this->assertNull(array_last($array));
    }

    public function testArrayFirstWithMixedKeys(): void
    {
        $array = [10 => 'ten', 5 => 'five', 1 => 'one'];
        $this->assertSame('ten', array_first($array));
    }

    public function testArrayLastWithMixedKeys(): void
    {
        $array = [10 => 'ten', 5 => 'five', 1 => 'one'];
        $this->assertSame('one', array_last($array));
    }

    public function testArrayFirstWithStringKeys(): void
    {
        $array = ['a' => 'alpha', 'b' => 'beta'];
        $this->assertSame('alpha', array_first($array));
    }

    public function testArrayLastWithStringKeys(): void
    {
        $array = ['a' => 'alpha', 'b' => 'beta'];
        $this->assertSame('beta', array_last($array));
    }

    public function testArrayFirstWithObjects(): void
    {
        $obj1 = (object)['id' => 1];
        $obj2 = (object)['id' => 2];
        $array = [$obj1, $obj2];
        
        $this->assertSame($obj1, array_first($array));
    }

    public function testArrayLastWithObjects(): void
    {
        $obj1 = (object)['id' => 1];
        $obj2 = (object)['id' => 2];
        $array = [$obj1, $obj2];
        
        $this->assertSame($obj2, array_last($array));
    }
}
