<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Commands\DatabaseSeedCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Core\Console\ConsoleOutput;
use Core\Database\Database;
use PHPUnit\Framework\TestCase;

class DatabaseSeedCommandTest extends TestCase
{
    public function testSeedCommandCreatesUsers(): void
    {
        $output = new ConsoleOutput();
        
        // Create an in-memory SQLite database for testing
        $db = new Database('sqlite::memory:');
        $db->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, created_at TEXT)');
        
        $userRepo = new UserRepository($db);
        $command = new DatabaseSeedCommand($userRepo);
        
        // Execute the command with force flag
        $exitCode = $command->execute($output, count: 3, force: true);
        
        $this->assertSame(0, $exitCode);
        
        // Verify users were created
        $users = $userRepo->findAll();
        $this->assertCount(3, $users);
        $this->assertStringContainsString('Test User 1', $users[0]->name);
        $this->assertStringContainsString('user1+seed@example.com', $users[0]->email);
    }

    public function testSeedCommandWithoutForceReturnsEarly(): void
    {
        $output = new ConsoleOutput();
        
        // Create an in-memory SQLite database
        $db = new Database('sqlite::memory:');
        $db->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, created_at TEXT)');
        
        $userRepo = new UserRepository($db);
        $command = new DatabaseSeedCommand($userRepo);
        
        // Execute without force flag
        $exitCode = $command->execute($output, count: 5, force: false);
        
        // Should return 0 but not create any users
        $this->assertSame(0, $exitCode);
        $this->assertCount(0, $userRepo->findAll());
    }
}
