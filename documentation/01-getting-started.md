# Getting Started

## Requirements

- **PHP 8.5+** (required for URI extension, #[\NoDiscard], clone-with)
- Composer
- Docker (recommended for PHP 8.5 environment)

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Configure your database in `config/database.php`

## Running with Docker (Recommended)

The project includes a `docker-compose.yml` with **PHP 8.5** and **MariaDB** (tested and recommended).

1. Start the stack:
   ```bash
   docker-compose up -d --build
   # Or use the Makefile:
   make up
   ```

2. Run database migrations:
   ```bash
   make migrate
   # Or: docker exec lucid-php-app php bin/migrate up
   ```

3. Seed the database (optional):
   ```bash
   make seed
   # Or: docker exec lucid-php-app php bin/seed
   ```

4. Run tests:
   ```bash
   make test
   ```

5. Run performance benchmarks (optional):
   ```bash
   make benchmark
   ```

6. Access the app at `http://localhost:8000`

## Running Locally

1. Configure your database in `config/database.php`
2. Run migrations:
   ```bash
   php bin/migrate up
   ```
3. Start the built-in server:
   ```bash
   php -S localhost:8000 -t public
   ```

## Directory Structure

| Path | Description |
|------|-------------|
| `bin/` | CLI executables (`migrate`, `seed`) |
| `config/` | **Configuration Files** (PHP arrays) |
| `database/` | SQL Migrations and Seeder classes |
| `documentation/` | **Framework Documentation** |
| `examples/` | Usage examples for all framework features |
| `public/` | Web entry point (`index.php`) |
| `src/App/` | **Application Logic** (Controllers, Entities, Repositories) |
| `src/Core/` | **Framework Kernel** (Router, Container, Database wrapper) |
| `tests/` | PHPUnit Tests |
| `storage/` | Cache, logs, and uploaded files |

## Next Steps

- Read [Philosophy](02-philosophy.md) to understand the framework's principles
- Learn about [PHP 8.5 Features](03-php85-features.md) used in the framework
- Explore [Configuration](04-configuration.md) to set up your environment
- Check [Routing](05-routing.md) to create your first endpoint
