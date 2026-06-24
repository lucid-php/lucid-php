# Lucid-PHP

**Clear PHP, No Magic.** A strict, explicit PHP framework running on **PHP 8.5+**.

---

## Philosophy

- **No Magic**: No facades, no global helper functions, no hidden autowiring or "discovery". Things are explicitly registered.
- **Strict Typing**: All files declare strict types. Controllers, Repositories, and Entities enforce types.
- **Attributes-First**: Routing, Middleware, and Validation are configured using standard PHP Attributes.
- **Modern PHP 8.5**: Built to leverage URI Extension, #[\NoDiscard], Clone-With, array_first/last, Readonly classes, and Property Hooks.
- **SQL as First-Class**: Raw SQL in migrations, explicit queries, Repository pattern over ORM magic.
- **Explicit Over Convenient**: If it saves 2 lines but hides behavior, we reject it.

Read the full [Philosophy Document](documentation/02-philosophy.md) to understand the framework's core principles.

---

## Quick Start

```bash
# Clone and install
composer install

# Start with Docker
docker-compose up -d
make migrate

# Or run locally
php -S localhost:8000 -t public

# Run tests
./vendor/bin/phpunit
```

See [Getting Started](documentation/01-getting-started.md) for detailed installation instructions.

---

## Features

### Core Framework
- **Routing**: Attribute-based routing with middleware support → [Docs](documentation/05-routing.md)
- **API Responses**: Standardized response structure for consistency → [Docs](documentation/05-routing.md#api-responses)
- **Dependency Injection**: Explicit container with autowiring → [Docs](documentation/04-configuration.md)
- **Database**: Raw SQL with PDO wrapper, migrations, transactions → [Docs](documentation/06-database.md)
- **Validation**: Attribute-based validation on DTOs → [Docs](documentation/07-validation.md)

### Subsystems
- **Event System**: Explicit event dispatcher with listeners → [Docs](documentation/08-events.md)
- **Mail**: SMTP, Log, and Array drivers with queue support → [Docs](documentation/09-mail.md)
- **Queue**: Background job processing (sync/database drivers) → [Docs](documentation/10-queue.md)
- **Scheduler**: Cron-like task scheduling → [Docs](documentation/11-scheduler.md)
- **Console**: Attribute-based CLI commands → [Docs](documentation/12-console.md)

### Application Composition & Advanced Features
- **Modules**: Explicit application composition with dependency validation → [Docs](documentation/14-modules.md)
- **GraphQL**: Type-safe GraphQL endpoint support → [Docs](documentation/15-graphql.md)
- **Pipelines**: Ordered workflows with explicit extension points → [Docs](documentation/16-pipelines.md)
- **Service Contracts**: Typed interfaces for domain logic → [Docs](documentation/17-service-contracts.md)

### PHP 8.5 Features
- Native URI Extension for RFC 3986 compliance
- `#[\NoDiscard]` attribute for return value enforcement
- Clone-with for immutable object updates
- `array_first()` and `array_last()` native functions
- Final constructor properties

Read about [PHP 8.5 Features](documentation/03-php85-features.md) used in the framework.

> Lucid does not cache module, pipeline, or GraphQL registry output by default. The registries are deterministic and can be cached later if profiling shows a real need.

---

## Documentation

📚 **[Complete Documentation](documentation/README.md)**

### Quick Links
1. [Getting Started](documentation/01-getting-started.md) - Installation and setup
2. [Philosophy](documentation/02-philosophy.md) - Core principles and decision framework
3. [PHP 8.5 Features](documentation/03-php85-features.md) - Modern PHP capabilities
4. [Configuration](documentation/04-configuration.md) - Config files and container
5. [Routing](documentation/05-routing.md) - Attribute-based routing
6. [Database](documentation/06-database.md) - Raw SQL, migrations, repositories
7. [Validation](documentation/07-validation.md) - DTO validation with attributes
8. [Events](documentation/08-events.md) - Event dispatcher and listeners
9. [Mail](documentation/09-mail.md) - Email with SMTP/Log/Array drivers
10. [Queue](documentation/10-queue.md) - Background job processing
11. [Scheduler](documentation/11-scheduler.md) - Cron-like task scheduling
12. [Console](documentation/12-console.md) - CLI commands with attributes
13. [Testing](documentation/13-testing.md) - PHPUnit testing patterns

---

## Project Structure

```
Framework/
├── bin/                 # CLI executables (migrate, seed)
├── config/              # Configuration files (PHP arrays)
├── database/            # Migrations and seeders (raw SQL)
├── documentation/       # Framework documentation
├── examples/            # Usage examples for all features
├── public/              # Web entry point (index.php)
├── src/
│   ├── App/            # Your application code
│   │   ├── Commands/   # Console commands
│   │   ├── Controllers/
│   │   ├── DTO/
│   │   ├── Entity/
│   │   ├── Event/
│   │   ├── Job/        # Queue jobs & scheduled tasks
│   │   ├── Listener/
│   │   ├── Middleware/
│   │   └── Repository/
│   └── Core/           # Framework kernel
│       ├── Attribute/
│       ├── Cache/
│       ├── Collection/
│       ├── Config/
│       ├── Console/
│       ├── Database/
│       ├── Event/
│       ├── Http/
│       ├── Log/
│       ├── Mail/
│       ├── Middleware/
│       ├── Queue/
│       ├── Schedule/
│       ├── Security/
│       ├── Session/
│       ├── Upload/
│       ├── Validation/
│       └── View/
├── storage/             # Cache, logs, uploads
├── tests/               # PHPUnit tests
└── vendor/              # Composer dependencies
```

---

## Example: Creating an API Endpoint

```php
<?php

namespace App\Controllers;

use Core\Attribute\Route;
use Core\Http\Request;
use Core\Http\Response;
use App\DTO\CreateUserDTO;
use App\Repository\UserRepository;

class UserController
{
    public function __construct(
        private readonly UserRepository $users
    ) {}

    #[Route('POST', '/users')]
    public function create(CreateUserDTO $dto): Response
    {
        $user = $this->users->create(
            name: $dto->name,
            email: $dto->email,
            password: password_hash($dto->password, PASSWORD_BCRYPT)
        );

        return Response::json($user, 201);
    }

    #[Route('GET', '/users/:id')]
    public function show(Request $request): Response
    {
        $user = $this->users->find((int) $request->params['id']);
        
        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }

        return Response::json($user);
    }
}
```

**No magic**. No facades. No global helpers. Just explicit, typed, traceable code.

---

## Testing

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific category
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Feature/

# With readable output
./vendor/bin/phpunit --testdox
```

See [Testing Documentation](documentation/13-testing.md) for patterns and examples.

---

## Contributing

This framework follows strict architectural principles. Before contributing:

1. Read [PHILOSOPHY.md](PHILOSOPHY.md) - Non-negotiable design constraints
2. Understand the "Zero Magic" principle
3. Every feature must be explicitly registered
4. All code must be strictly typed
5. If you can't Command+Click to it, it's magic (and rejected)

---

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## Philosophy Summary

> **"We're not building the fastest framework. We're building the most honest one."**

This framework rejects:
- ❌ Magic methods and facades
- ❌ Global helper functions
- ❌ Auto-discovery by convention
- ❌ Hidden behavior and implicit wiring
- ❌ ORMs that hide SQL

This framework embraces:
- ✅ Explicit registration and dependency injection
- ✅ Strict typing everywhere
- ✅ Attributes for metadata
- ✅ Raw SQL as first-class citizen
- ✅ Traceable execution flow
- ✅ Modern PHP 8.5 features

**Target audience**: Senior developers tired of debugging magic. Teams maintaining long-lived applications. Developers who want to understand, not just use.

Read the complete [Philosophy Document](documentation/02-philosophy.md) for the full story.
