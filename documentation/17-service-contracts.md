# Service Contracts & Conventions

Lucid-PHP uses explicit service contracts rather than convention-based discovery.

## Core Principles

1. **Explicit registration**: Services are registered in the container explicitly
2. **Interface contracts**: Service interfaces define the contract
3. **No convention-based binding**: `Interface` does not auto-bind to `Implementation`
4. **Dependency injection**: Use constructor injection
5. **Testability**: Interfaces allow for testing with mock implementations

## Service Registration

### In a Module

Register services in the `register()` phase:

```php
<?php

namespace App\Modules\User;

use Core\Container;
use Core\Module\ModuleInterface;

public function register(Container $container): void
{
    // Bind interface to implementation
    $container->set(UserServiceInterface::class, UserService::class);
    $container->set(UserRepositoryInterface::class, UserRepository::class);

    // Bind to a factory if needed
    // (explicit config object, no env() helper)
    $container->set(
        EmailServiceInterface::class,
        new EmailService(
            host: $config->getString('mail.smtp.host'),
            port: $config->getInt('mail.smtp.port'),
        )
    );
}
```

### Singleton vs. Transient

The container decides whether to create a new instance or reuse:

```php
// Singleton (single instance, reused)
$container->set(DatabaseInterface::class, Database::class);

// Transient (new instance each time) - implement your own factory
$container->set(
    RequestInterface::class,
    fn(Container $c) => new Request($c->get(Uri::class)),
);
```

## Defining Service Contracts

A service contract is a typed interface:

```php
<?php

declare(strict_types=1);

namespace App\Services\User;

interface UserServiceInterface
{
    public function create(CreateUserCommand $command): UserResult;

    public function findById(string $id): ?User;

    public function update(string $id, UpdateUserCommand $command): UserResult;
}
```

## Commands and Results

Use typed DTOs for commands and results:

```php
<?php

declare(strict_types=1);

namespace App\Services\User;

// Command: Input to a service
final readonly class CreateUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $name,
    ) {}
}

// Result: Output from a service
final readonly class UserResult
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
    ) {}
}

// Usage
final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    public function create(CreateUserCommand $command): UserResult
    {
        $user = $this->repository->create(
            email: $command->email,
            password: password_hash($command->password, PASSWORD_BCRYPT),
            name: $command->name,
        );

        return new UserResult(
            id: $user->id,
            email: $user->email,
            name: $user->name,
        );
    }

    public function findById(string $id): ?User
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, UpdateUserCommand $command): UserResult
    {
        $user = $this->repository->update($id, $command->toArray());

        return new UserResult(
            id: $user->id,
            email: $user->email,
            name: $user->name,
        );
    }
}
```

## Using Services

Inject services into controllers, listeners, etc.:

```php
<?php

namespace App\Http\Controllers;

use Core\Http\Request;
use Core\Http\Response;
use App\Services\User\UserServiceInterface;
use App\Services\User\CreateUserCommand;

final class UserController
{
    public function __construct(
        private UserServiceInterface $userService,
    ) {}

    public function store(Request $request): Response
    {
        $command = new CreateUserCommand(
            email: $request->input('email'),
            password: $request->input('password'),
            name: $request->input('name'),
        );

        $result = $this->userService->create($command);

        return new Response(json_encode(['user' => $result]), 201);
    }

    public function show(Request $request): Response
    {
        $userId = $request->route('id');
        $user = $this->userService->findById($userId);

        if (!$user) {
            return new Response(json_encode(['error' => 'Not found']), 404);
        }

        return new Response(json_encode(['user' => $user]));
    }
}
```

## Repositories

Repositories provide data access:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Database\AbstractRepository;

interface UserRepositoryInterface
{
    public function create(string $email, string $password, string $name): User;

    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function update(string $id, array $data): User;

    public function delete(string $id): bool;
}

final class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    protected string $table = 'users';

    public function create(string $email, string $password, string $name): User
    {
        $this->db->insert($this->table, [
            'email' => $email,
            'password' => $password,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return new User(...);
    }

    public function findById(string $id): ?User
    {
        $row = $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id],
        );

        return $row ? new User(...) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email],
        );

        return $row ? new User(...) : null;
    }

    public function update(string $id, array $data): User
    {
        $this->db->update($this->table, $data, ['id' => $id]);

        return $this->findById($id);
    }

    public function delete(string $id): bool
    {
        return $this->db->delete($this->table, ['id' => $id]) > 0;
    }
}
```

## Testing Services

Mock implementations for testing:

```php
<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\User\UserServiceInterface;
use App\Services\User\CreateUserCommand;
use App\Services\User\UserResult;

final class MockUserService implements UserServiceInterface
{
    public function create(CreateUserCommand $command): UserResult
    {
        return new UserResult(
            id: '123',
            email: $command->email,
            name: $command->name,
        );
    }

    public function findById(string $id): ?User
    {
        return null;
    }

    public function update(string $id, UpdateUserCommand $command): UserResult
    {
        return new UserResult(id: $id, email: $command->email, name: $command->name);
    }
}

final class UserControllerTest extends TestCase
{
    public function testStoreCreatesUser(): void
    {
        $service = new MockUserService();

        $command = new CreateUserCommand(
            email: 'test@example.com',
            password: 'secret',
            name: 'Test User',
        );

        $result = $service->create($command);

        $this->assertSame('test@example.com', $result->email);
    }
}
```

## Best Practices

### 1. Keep Interfaces Simple

```php
// Good: Single responsibility
interface UserServiceInterface
{
    public function create(CreateUserCommand $command): UserResult;
}

// Bad: Too many responsibilities
interface AllServiceInterface
{
    public function createUser(...): UserResult;
    public function sendEmail(...): void;
    public function processPayment(...): void;
}
```

### 2. Use Result Objects

```php
// Good: Typed result
public function create(CreateUserCommand $command): UserResult;

// Bad: Untyped or mixed return
public function create(array $data): array|User|null;
```

### 3. Use Commands for Input

```php
// Good: Explicit command
public function create(CreateUserCommand $command): UserResult;

// Bad: Magic array
public function create(array $data): UserResult;
```

### 4. Inject Dependencies

```php
// Good: Explicit dependencies
final readonly class UserService
{
    public function __construct(
        private UserRepository $repository,
        private EventDispatcher $events,
    ) {}
}

// Bad: Static access
class UserService
{
    public function __construct()
    {
        $this->events = EventDispatcher::getInstance();
    }
}
```

### 5. Register All Services

```php
// Good: Everything registered
$container->set(UserServiceInterface::class, UserService::class);
$container->set(UserRepositoryInterface::class, UserRepository::class);

// Bad: Relying on convention
// "Just new it up when needed"
```

## Future Enhancements

- Service validation at boot time
- Circular dependency detection for services
- Performance metrics for service calls
- Service lifecycle hooks

Lucid does not cache module, pipeline, or GraphQL registry output by default. The registries are deterministic and can be cached later if profiling shows a real need.
