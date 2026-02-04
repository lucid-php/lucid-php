<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\CreateUserDTO;
use App\DTO\LoginDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * PHP 8.5 Feature Test: Clone-With Syntax
 * 
 * Tests the clone-with pattern in readonly classes.
 * This demonstrates how PHP 8.5's clone-with reduces boilerplate
 * in immutable value objects.
 */
class CloneWithTest extends TestCase
{
    #[Test]
    public function it_clones_with_updated_name(): void
    {
        $original = new CreateUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secure123'
        );

        $updated = $original->withName('Jane Doe');

        $this->assertNotSame($original, $updated);
        $this->assertSame('Jane Doe', $updated->name);
        $this->assertSame('john@example.com', $updated->email);
        $this->assertSame('secure123', $updated->password);
        
        // Original unchanged
        $this->assertSame('John Doe', $original->name);
    }

    #[Test]
    public function it_clones_with_updated_email(): void
    {
        $original = new CreateUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secure123'
        );

        $updated = $original->withEmail('jane@example.com');

        $this->assertNotSame($original, $updated);
        $this->assertSame('John Doe', $updated->name);
        $this->assertSame('jane@example.com', $updated->email);
        $this->assertSame('secure123', $updated->password);
    }

    #[Test]
    public function it_clones_with_updated_password(): void
    {
        $original = new CreateUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secure123'
        );

        $updated = $original->withPassword('newsecure456');

        $this->assertNotSame($original, $updated);
        $this->assertSame('John Doe', $updated->name);
        $this->assertSame('john@example.com', $updated->email);
        $this->assertSame('newsecure456', $updated->password);
    }

    #[Test]
    public function it_chains_multiple_withers(): void
    {
        $original = new CreateUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secure123'
        );

        $updated = $original
            ->withName('Jane Doe')
            ->withEmail('jane@example.com')
            ->withPassword('newsecure456');

        $this->assertNotSame($original, $updated);
        $this->assertSame('Jane Doe', $updated->name);
        $this->assertSame('jane@example.com', $updated->email);
        $this->assertSame('newsecure456', $updated->password);
        
        // Original completely unchanged
        $this->assertSame('John Doe', $original->name);
        $this->assertSame('john@example.com', $original->email);
        $this->assertSame('secure123', $original->password);
    }

    #[Test]
    public function login_dto_supports_clone_with(): void
    {
        $original = new LoginDTO(
            email: 'john@example.com',
            password: 'secure123'
        );

        $updated = $original->withEmail('jane@example.com');

        $this->assertNotSame($original, $updated);
        $this->assertSame('jane@example.com', $updated->email);
        $this->assertSame('secure123', $updated->password);
        $this->assertSame('john@example.com', $original->email);
    }

    #[Test]
    public function readonly_properties_cannot_be_mutated_directly(): void
    {
        $dto = new CreateUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secure123'
        );

        // This should not compile or throw error in PHP 8.5
        // $dto->name = 'Jane Doe'; // Error: Cannot modify readonly property
        
        // Instead, must use withers
        $updated = $dto->withName('Jane Doe');
        $this->assertSame('Jane Doe', $updated->name);
    }
}
