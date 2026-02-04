# Testing

## Test Structure

The framework uses PHPUnit with three test categories:

```
tests/
├── Unit/           # Framework core tests (Router, Database, Config, etc.)
├── Feature/        # End-to-end HTTP tests (full request/response cycle)
└── App/            # YOUR application tests (repositories, services, entities)
```

## Writing Application Tests

**Pattern: Test your repositories, services, and business logic directly.**

```php
<?php

namespace Tests\App;

use App\Repository\UserRepository;
use Core\Database\Database;
use Core\Database\Migrator;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private Database $db;
    private UserRepository $repository;

    protected function setUp(): void
    {
        // 1. Create in-memory database (explicit, no magic)
        $this->db = new Database('sqlite::memory:');

        // 2. Run migrations to set up schema
        $migrator = new Migrator($this->db, __DIR__ . '/../../database/migrations');
        $migrator->migrate();

        // 3. Instantiate repository (dependency injection)
        $this->repository = new UserRepository($this->db);
    }

    public function test_create_user_returns_user_entity(): void
    {
        $user = $this->repository->create('John', 'john@example.com', 'hashed');

        $this->assertEquals('John', $user->name);
        $this->assertIsInt($user->id);
    }
}
```

**Why This Pattern?**
- ✅ Explicit database setup (no global test traits)
- ✅ Standard PHPUnit - no custom assertions
- ✅ Test real code, not mocks (in-memory SQLite is fast)
- ✅ Can Command+Click to understand everything

## Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Specific category
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Feature/
./vendor/bin/phpunit tests/App/

# Single test file
./vendor/bin/phpunit tests/App/UserRepositoryTest.php

# With readable output
./vendor/bin/phpunit --testdox
```

**Example: See tests/App/UserRepositoryTest.php for a complete reference.**
