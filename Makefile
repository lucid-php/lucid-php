.PHONY: help build up down restart shell php-version test test-unit test-feature migrate seed composer install clean logs

# Default target
help:
	@echo "Lucid-PHP - Docker Commands"
	@echo ""
	@echo "Available commands:"
	@echo "  make build        - Build Docker images with PHP 8.5"
	@echo "  make up           - Start all containers"
	@echo "  make down         - Stop all containers"
	@echo "  make restart      - Restart all containers"
	@echo "  make shell        - Access PHP container shell"
	@echo "  make php-version  - Check PHP version in container"
	@echo "  make test         - Run full test suite"
	@echo "  make test-unit    - Run unit tests only"
	@echo "  make test-feature - Run feature tests only"
	@echo "  make benchmark    - Run PHP 8.5 performance benchmarks"
	@echo "  make migrate      - Run database migrations"
	@echo "  make seed         - Run database seeds"
	@echo "  make composer     - Run composer install"
	@echo "  make install      - Build, start, install deps, migrate"
	@echo "  make clean        - Stop containers and remove volumes"
	@echo "  make logs         - View container logs"

# Build Docker images
build:
	@echo "Building Docker images with PHP 8.5..."
	docker compose build

# Start containers
up:
	@echo "Starting containers..."
	docker compose up -d
	@echo "Waiting for database to be ready..."
	@sleep 5
	@echo "Containers started successfully!"

# Stop containers
down:
	@echo "Stopping containers..."
	docker compose down

# Restart containers
restart: down up

# Access container shell
shell:
	@echo "Accessing PHP container shell..."
	docker compose exec app /bin/sh

# Check PHP version
php-version:
	@echo "Checking PHP version in container..."
	docker compose exec app php --version

# Run full test suite
test:
	@echo "Running full test suite..."
	docker compose exec app ./vendor/bin/phpunit --testdox

# Run unit tests only
test-unit:
	@echo "Running unit tests..."
	docker compose exec app ./vendor/bin/phpunit tests/Unit --testdox

# Run feature tests only
test-feature:
	@echo "Running feature tests..."
	docker compose exec app ./vendor/bin/phpunit tests/Feature --testdox

# Run performance benchmarks
benchmark:
	@echo "Running PHP 8.5 performance benchmarks..."
	@echo ""
	docker compose exec app php benchmarks/php85-features.php

# Run database migrations
migrate:
	@echo "Running database migrations..."
	docker compose exec app php bin/migrate up

# Run database seeds
seed:
	@echo "Running database seeds..."
	docker compose exec app php bin/seed

# Run composer install
composer:
	@echo "Running composer install..."
	docker compose exec app composer install

# Full setup (build, start, install, migrate)
install: build up composer migrate
	@echo ""
	@echo "Setup complete! Framework is ready."
	@echo "Run 'make test' to verify everything works."

# Clean up (stop containers and remove volumes)
clean:
	@echo "Cleaning up containers and volumes..."
	docker compose down -v
	@echo "Clean complete!"

# View logs
logs:
	docker compose logs -f app

# Run composer update
update:
	@echo "Running composer update..."
	docker compose exec app composer update

# Run PHP linter on all files
lint:
	@echo "Checking PHP syntax..."
	docker compose exec app find src tests -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"

# Access MySQL container
mysql:
	@echo "Accessing MySQL container..."
	docker compose exec db mysql -u user -ppassword framework

# Run specific test file
# Usage: make test-file FILE=tests/Unit/SomeTest.php
test-file:
	@echo "Running test file: $(FILE)"
	docker compose exec app ./vendor/bin/phpunit $(FILE) --testdox

# Run tests with coverage (requires xdebug)
test-coverage:
	@echo "Running tests with coverage..."
	docker compose exec app ./vendor/bin/phpunit --coverage-html coverage

# Rebuild without cache
rebuild:
	@echo "Rebuilding containers without cache..."
	docker compose build --no-cache
	docker compose up -d

# Show running containers
ps:
	docker compose ps

# Check if containers are healthy
health:
	@echo "Checking container health..."
	@docker compose ps
	@echo ""
	@echo "PHP Version:"
	@docker compose exec app php --version | head -n 1
	@echo ""
	@echo "Database status:"
	@docker compose exec db mysqladmin -u user -ppassword ping
