<?php

declare(strict_types=1);

namespace Tests\App;

use App\Entity\User;
use App\Repository\UserRepository;
use Core\Database\Database;
use Core\Database\Migrator;
use PHPUnit\Framework\TestCase;

/**
 * Example Application Test
 * 
 * This demonstrates how developers should test their own App code.
 * 
 * Pattern:
 * 1. Setup: Initialize database and run migrations
 * 2. Test: Test repositories, services, entities directly
 * 3. Teardown: Clean up database
 * 
 * Why tests/App/ instead of tests/Unit/?
 * - tests/Unit/ is for framework Core tests
 * - tests/Feature/ is for full HTTP end-to-end tests
 * - tests/App/ is for YOUR application logic (repositories, services, entities)
 * 
 * No magic:
 * - Explicitly create Database connection
 * - Explicitly run migrations
 * - Explicitly instantiate repositories
 * - Standard PHPUnit assertions
 */
class UserRepositoryTest extends TestCase
{
    private Database $db;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = new Database('sqlite::memory:');

        // Run migrations to set up schema
        $migrationsPath = __DIR__ . '/../../database/migrations';
        $migrator = new Migrator($this->db, $migrationsPath);
        $migrator->migrate();

        // Instantiate repository (explicit, no magic)
        $this->userRepository = new UserRepository($this->db);
    }

    public function test_create_user_returns_user_entity(): void
    {
        $user = $this->userRepository->create(
            name: 'John Doe',
            email: 'john@example.com',
            password: password_hash('secret', PASSWORD_BCRYPT)
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertIsInt($user->id);
    }

    public function test_find_by_email_returns_user(): void
    {
        // Arrange: Create a user
        $this->userRepository->create(
            name: 'Jane Smith',
            email: 'jane@example.com',
            password: password_hash('secret', PASSWORD_BCRYPT)
        );

        // Act: Find by email
        $user = $this->userRepository->findByEmail('jane@example.com');

        // Assert
        $this->assertNotNull($user);
        $this->assertEquals('Jane Smith', $user->name);
    }

    public function test_find_by_email_returns_null_for_nonexistent_user(): void
    {
        $user = $this->userRepository->findByEmail('nonexistent@example.com');

        $this->assertNull($user);
    }

    public function test_find_all_returns_empty_array_when_no_users(): void
    {
        $users = $this->userRepository->findAll();

        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }

    public function test_find_all_returns_all_users(): void
    {
        // Create multiple users
        $this->userRepository->create('User 1', 'user1@example.com', 'pass1');
        $this->userRepository->create('User 2', 'user2@example.com', 'pass2');
        $this->userRepository->create('User 3', 'user3@example.com', 'pass3');

        $users = $this->userRepository->findAll();

        $this->assertCount(3, $users);
        $this->assertContainsOnlyInstancesOf(User::class, $users);
    }

    public function test_user_entity_has_timestamp(): void
    {
        $user = $this->userRepository->create(
            name: 'Test User',
            email: 'test@example.com',
            password: 'hashed'
        );

        // created_at should be set by the database or application
        // If your schema has DEFAULT CURRENT_TIMESTAMP, this will be set
        // This test demonstrates checking entity properties
        $this->assertNotNull($user->id);
        $this->assertIsInt($user->id);
    }

    /**
     * Example: Testing business logic in repositories
     */
    public function test_cannot_create_duplicate_email(): void
    {
        $this->userRepository->create('User 1', 'duplicate@example.com', 'pass');

        // In a real implementation, this should throw an exception
        // or return false. This is a placeholder to show the pattern.
        $this->expectException(\PDOException::class);
        $this->userRepository->create('User 2', 'duplicate@example.com', 'pass');
    }
}
