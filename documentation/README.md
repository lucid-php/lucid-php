# Lucid-PHP Documentation

Complete documentation for Lucid-PHP - a strict, explicit, zero-magic PHP 8.5+ framework.

---

## Table of Contents

### Getting Started
1. **[Getting Started](01-getting-started.md)**
   - Requirements
   - Installation
   - Running with Docker
   - Running Locally
   - Directory Structure

2. **[Philosophy](02-philosophy.md)**
   - Core Principles (Zero Magic, Strict Typing, etc.)
   - What We're Building Against
   - Decision Framework
   - Target Audience

3. **[PHP 8.5 Features](03-php85-features.md)**
   - Native URI Extension
   - #[\NoDiscard] Attribute
   - Clone-With for Readonly Classes
   - array_first() / array_last()
   - Pipe Operator
   - Final Constructor Properties
   - Performance Benchmarks

### Core Framework

4. **[Configuration](04-configuration.md)**
   - Config Files (PHP Arrays)
   - Dependency Injection Container
   - Service Registration
   - Environment Configuration

5. **[Routing](05-routing.md)**
   - Attribute-Based Routing
   - Route Parameters
   - Query Parameters
   - Middleware (Global, Class, Method-Level)
   - Request/Response
   - Pagination

6. **[Database](06-database.md)**
   - Raw SQL with PDO
   - Migrations (Pure SQL)
   - Repository Pattern
   - Transactions
   - Database Seeding

7. **[Validation](07-validation.md)**
   - Attribute-Based Validation
   - Data Transfer Objects (DTOs)
   - Built-in Validation Rules
   - Custom Validation
   - Error Handling

### Subsystems

8. **[Events](08-events.md)**
   - Event Dispatcher
   - Creating Events
   - Creating Listeners
   - Registering Listeners
   - Dispatching Events

9. **[Mail](09-mail.md)**
   - SMTP Driver
   - Log Driver (Development)
   - Array Driver (Testing)
   - Queued Mail
   - API Reference

10. **[Queue](10-queue.md)**
    - Job Classes
    - Queue Drivers (Sync, Database)
    - Dispatching Jobs
    - Queue Worker
    - Failed Jobs

11. **[Scheduler](11-scheduler.md)**
    - Cron-Like Task Scheduling
    - Creating Scheduled Jobs
    - Cron Expressions
    - Helper Methods
    - Production Setup
    - Testing

12. **[Console](12-console.md)**
    - Creating Commands
    - Command Attributes
    - Arguments and Options
    - Output Formatting
    - Built-in Commands

### Testing & Development

13. **[Testing](13-testing.md)**
    - Test Structure
    - Unit Tests
    - Feature Tests
    - Testing Patterns
    - Running Tests

---

## Quick Reference

### Common Tasks

**Create a new endpoint:**
```php
#[Route('GET', '/users')]
public function index(): Response
{
    return Response::json($this->users->all());
}
```

**Create a migration:**
```bash
# Create 003_create_posts_table.up.sql
CREATE TABLE posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    created_at TEXT NOT NULL
);
```

**Create a scheduled job:**
```php
class BackupJob implements ScheduledJobInterface
{
    public function schedule(): string
    {
        return CronExpression::daily();
    }
}
```

**Create a console command:**
```php
#[ConsoleCommand(name: 'cache:clear', description: 'Clear cache')]
class CacheClearCommand implements CommandInterface
{
    public function execute(OutputInterface $output): int
    {
        // Clear cache logic
        return 0;
    }
}
```

---

## Philosophy Quick Reference

### ✅ Do This

- Explicitly register controllers, commands, listeners
- Use constructor injection with typed parameters
- Write raw SQL in migrations
- Use attributes for routing and validation
- Type everything (parameters, returns, properties)
- Make execution flow traceable

### ❌ Don't Do This

- Use facades or global helpers
- Auto-discover by directory scanning
- Use magic methods (`__call`, `__get`)
- Hide SQL behind ORM abstractions
- Use `mixed` types without good reason
- Create convenience that hides behavior

---

## Architecture Overview

```
Request → Router → Middleware Stack → Controller → Repository → Database
                                    ↓
                                Response
```

**Every step is explicit:**
- Routes declared with `#[Route]` attributes
- Middleware registered explicitly
- Dependencies injected via constructor
- Repositories use explicit SQL queries
- Responses are typed objects

**No magic. No globals. No hidden behavior.**

---

## Framework Components

| Component | Purpose | Documentation |
|-----------|---------|---------------|
| Router | Attribute-based routing | [05-routing.md](05-routing.md) |
| Container | Dependency injection | [04-configuration.md](04-configuration.md) |
| Database | PDO wrapper + migrations | [06-database.md](06-database.md) |
| Validator | DTO validation | [07-validation.md](07-validation.md) |
| EventDispatcher | Pub/sub events | [08-events.md](08-events.md) |
| Mailer | Email sending | [09-mail.md](09-mail.md) |
| Queue | Background jobs | [10-queue.md](10-queue.md) |
| Scheduler | Cron-like tasks | [11-scheduler.md](11-scheduler.md) |
| Console | CLI commands | [12-console.md](12-console.md) |

---

## Need Help?

1. Check the relevant documentation section above
2. Look at examples in `/examples` directory
3. Run tests to see usage patterns: `./vendor/bin/phpunit`
4. Read the [Philosophy](02-philosophy.md) to understand design decisions

---

## Contributing to Documentation

When updating documentation:

1. Keep examples explicit and traceable
2. Show the "why" not just the "how"
3. Contrast with "magic" alternatives
4. Include type hints in all examples
5. Maintain the zero-magic philosophy

---

**Remember**: This framework prioritizes understanding over convenience. If documentation seems verbose, that's intentional. We want you to understand exactly how everything works.
