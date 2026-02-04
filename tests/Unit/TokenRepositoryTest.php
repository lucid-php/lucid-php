<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entity\User;
use App\Repository\TokenRepository;
use Core\Database\Database;
use PHPUnit\Framework\TestCase;

class TokenRepositoryTest extends TestCase
{
    private Database $db;
    private TokenRepository $repository;

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');
        
        // Create users and tokens tables
        $this->db->execute("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                password TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->execute("
            CREATE TABLE personal_access_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // Insert test user
        $this->db->execute(
            "INSERT INTO users (name, email, password) VALUES (?, ?, ?)",
            ['John Doe', 'john@example.com', password_hash('password', PASSWORD_BCRYPT)]
        );

        $this->repository = new TokenRepository($this->db);
    }

    public function testCreateToken(): void
    {
        $token = $this->repository->createToken(1);

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testFindUserByToken(): void
    {
        $token = $this->repository->createToken(1);

        $user = $this->repository->findUserByToken($token);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John Doe', $user->name);
    }

    public function testFindUserByInvalidTokenReturnsNull(): void
    {
        $user = $this->repository->findUserByToken('invalid-token');

        $this->assertNull($user);
    }
}
