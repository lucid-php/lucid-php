<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entity\User;
use App\Repository\UserRepository;
use Core\Database\Database;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private Database $db;
    private UserRepository $repository;

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');
        
        // Create users table
        $this->db->execute("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                password TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->repository = new UserRepository($this->db);
    }

    public function testCreateUser(): void
    {
        $user = $this->repository->create('John Doe', 'john@example.com', 'password123');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertNotNull($user->id);
        $this->assertTrue(password_verify('password123', $user->password));
    }

    public function testFindByEmail(): void
    {
        $this->repository->create('John Doe', 'john@example.com', 'password123');

        $user = $this->repository->findByEmail('john@example.com');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John Doe', $user->name);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $user = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($user);
    }

    public function testFindAll(): void
    {
        $this->repository->create('John Doe', 'john@example.com', 'password123');
        $this->repository->create('Jane Doe', 'jane@example.com', 'password456');

        $users = $this->repository->findAll();

        $this->assertCount(2, $users);
        $this->assertContainsOnlyInstancesOf(User::class, $users);
    }
}
