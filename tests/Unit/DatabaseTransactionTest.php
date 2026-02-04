<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Database\Database;
use Core\Database\DatabaseException;
use PHPUnit\Framework\TestCase;

class DatabaseTransactionTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use in-memory SQLite for testing
        $this->db = new Database('sqlite::memory:');
        
        // Create test table
        $this->db->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');
    }

    public function test_begin_transaction_starts_transaction(): void
    {
        $this->assertFalse($this->db->inTransaction());
        
        $this->db->beginTransaction();
        
        $this->assertTrue($this->db->inTransaction());
        
        $this->db->rollback(); // Cleanup
    }

    public function test_commit_makes_changes_permanent(): void
    {
        $this->db->beginTransaction();
        
        $this->db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['John', 'john@example.com']);
        
        $this->db->commit();
        
        $users = $this->db->query('SELECT * FROM users');
        
        $this->assertCount(1, $users);
        $this->assertSame('John', $users[0]['name']);
    }

    public function test_rollback_discards_changes(): void
    {
        $this->db->beginTransaction();
        
        $this->db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['John', 'john@example.com']);
        
        $this->db->rollback();
        
        $users = $this->db->query('SELECT * FROM users');
        
        $this->assertCount(0, $users);
    }

    public function test_begin_transaction_throws_if_already_in_transaction(): void
    {
        $this->db->beginTransaction();
        
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Cannot start transaction: transaction already active');
        
        $this->db->beginTransaction();
    }

    public function test_commit_throws_if_not_in_transaction(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Cannot commit: no active transaction');
        
        $this->db->commit();
    }

    public function test_rollback_throws_if_not_in_transaction(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Cannot rollback: no active transaction');
        
        $this->db->rollback();
    }

    public function test_transaction_method_commits_on_success(): void
    {
        $result = $this->db->transaction(function () {
            $this->db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['John', 'john@example.com']);
            $this->db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['Jane', 'jane@example.com']);
            return 'success';
        });
        
        $this->assertSame('success', $result);
        $this->assertFalse($this->db->inTransaction());
        
        $users = $this->db->query('SELECT * FROM users');
        $this->assertCount(2, $users);
    }

    public function test_transaction_method_rollbacks_on_exception(): void
    {
        try {
            $this->db->transaction(function () {
                $this->db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['John', 'john@example.com']);
                throw new \Exception('Something went wrong');
            });
            $this->fail('Exception should have been thrown');
        } catch (\Exception $e) {
            $this->assertSame('Something went wrong', $e->getMessage());
        }
        
        $this->assertFalse($this->db->inTransaction());
        
        $users = $this->db->query('SELECT * FROM users');
        $this->assertCount(0, $users);
    }

    public function test_transaction_method_returns_callback_result(): void
    {
        $result = $this->db->transaction(function () {
            $this->db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['John', 'john@example.com']);
            return ['id' => 1, 'name' => 'John'];
        });
        
        $this->assertSame(['id' => 1, 'name' => 'John'], $result);
    }

    public function test_multiple_operations_in_transaction(): void
    {
        $this->db->beginTransaction();
        
        $this->db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['User1', 'user1@example.com']);
        $this->db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['User2', 'user2@example.com']);
        $this->db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['User3', 'user3@example.com']);
        
        $this->db->commit();
        
        $users = $this->db->query('SELECT * FROM users');
        $this->assertCount(3, $users);
    }

    public function test_transaction_rollback_on_constraint_violation(): void
    {
        // Create table with unique constraint
        $this->db->execute('CREATE TABLE products (id INTEGER PRIMARY KEY, sku TEXT UNIQUE, name TEXT)');
        
        // Insert first product
        $this->db->execute('INSERT INTO products (sku, name) VALUES (?, ?)', ['SKU001', 'Product 1']);
        
        try {
            $this->db->transaction(function () {
                $this->db->execute('INSERT INTO products (sku, name) VALUES (?, ?)', ['SKU002', 'Product 2']);
                // This will fail due to unique constraint
                $this->db->execute('INSERT INTO products (sku, name) VALUES (?, ?)', ['SKU001', 'Product 3']);
            });
            $this->fail('Exception should have been thrown');
        } catch (\Throwable) {
            // Expected
        }
        
        // Only the original product should exist
        $products = $this->db->query('SELECT * FROM products');
        $this->assertCount(1, $products);
        $this->assertSame('Product 1', $products[0]['name']);
    }

    public function test_in_transaction_returns_correct_state(): void
    {
        $this->assertFalse($this->db->inTransaction());
        
        $this->db->beginTransaction();
        $this->assertTrue($this->db->inTransaction());
        
        $this->db->commit();
        $this->assertFalse($this->db->inTransaction());
    }

    public function test_nested_transaction_in_callback_throws(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Cannot start transaction: transaction already active');
        
        $this->db->transaction(function () {
            $this->db->beginTransaction(); // Should throw
        });
    }

    public function test_transaction_with_return_value_types(): void
    {
        $intResult = $this->db->transaction(fn() => 42);
        $this->assertSame(42, $intResult);
        
        $arrayResult = $this->db->transaction(fn() => ['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $arrayResult);
        
        $nullResult = $this->db->transaction(fn() => null);
        $this->assertNull($nullResult);
        
        $objectResult = $this->db->transaction(fn() => (object)['id' => 1]);
        $this->assertEquals((object)['id' => 1], $objectResult);
    }
}
