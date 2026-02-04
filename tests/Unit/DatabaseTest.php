<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Database\Database;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');
        
        // Create test table
        $this->db->execute("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL
            )
        ");
    }

    public function testExecuteInsert(): void
    {
        $result = $this->db->execute(
            "INSERT INTO users (name, email) VALUES (:name, :email)",
            ['name' => 'John Doe', 'email' => 'john@example.com']
        );

        $this->assertTrue($result);
    }

    public function testQuery(): void
    {
        $this->db->execute(
            "INSERT INTO users (name, email) VALUES (:name, :email)",
            ['name' => 'John Doe', 'email' => 'john@example.com']
        );

        $results = $this->db->query("SELECT * FROM users WHERE email = :email", ['email' => 'john@example.com']);

        $this->assertCount(1, $results);
        $this->assertSame('John Doe', array_first($results)['name']);
    }

    public function testLastInsertId(): void
    {
        $this->db->execute(
            "INSERT INTO users (name, email) VALUES (:name, :email)",
            ['name' => 'John Doe', 'email' => 'john@example.com']
        );

        $id = $this->db->lastInsertId();

        $this->assertSame('1', $id);
    }

    public function testGetDriverName(): void
    {
        $this->assertSame('sqlite', $this->db->getDriverName());
    }
}
