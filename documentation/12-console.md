# Console Commands

The console framework follows strict explicit principles with attribute-based command declarations.

## Philosophy

- **No auto-discovery** - Commands must be registered explicitly in `console` script
- **Attribute-based declarations** - `#[ConsoleCommand]`, `#[Argument]`, `#[Option]` define command interface
- **Typed parameters** - Arguments and options are type-cast (int, bool, string, float)
- **Traceable** - Command+Click from attribute to command class

## Running Commands

```bash
# Show all available commands
php console
php console list
php console --help

# Run a specific command
php console migrate
php console migrate:rollback

# With options
php console migrate --step=5
php console migrate:rollback -s 2
```

## Built-in Commands

### migrate

Run pending database migrations.

```bash
php console migrate

# Run specific number of migrations
php console migrate --step=3
php console migrate -s 3
```

### migrate:rollback

Rollback the last migration(s).

```bash
php console migrate:rollback

# Rollback multiple migrations
php console migrate:rollback --step=5
php console migrate:rollback -s 5
```

## Creating Custom Commands

Commands are explicit classes with `#[ConsoleCommand]` attribute:

```php
<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attributes\ConsoleCommand;
use Core\Attributes\Argument;
use Core\Attributes\Option;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;

#[ConsoleCommand(
    name: 'greet',
    description: 'Greet a user by name'
)]
class GreetCommand implements CommandInterface
{
    public function execute(
        OutputInterface $output,
        #[Argument('name', 'The name to greet', required: true)]
        string $name,
        #[Option('greeting', 'g', 'Custom greeting', 'Hello')]
        string $greeting = 'Hello'
    ): int {
        $output->success("{$greeting}, {$name}!");
        return 0; // Exit code: 0 = success, 1 = error
    }
}
```

**Usage:**
```bash
php console greet John
# ✓ Hello, John!

php console greet Jane --greeting="Welcome"
# ✓ Welcome, Jane!

php console greet Magnus -g "Hi"
# ✓ Hi, Magnus!
```

## Registering Commands

Commands must be registered explicitly in the `console` script (no auto-discovery):

```php
// console
$console->registerCommands([
    MigrateCommand::class,
    MigrateRollbackCommand::class,
    GreetCommand::class,  // Your custom command
]);
```

## Command Attributes

### #[ConsoleCommand]

Declares a command (required on command class):

```php
#[ConsoleCommand(
    name: 'cache:clear',           // Command name (required)
    description: 'Clear all caches' // Help text (optional)
)]
class CacheClearCommand implements CommandInterface { }
```

### #[Argument]

Declares a positional argument (applied to method parameters):

```php
public function execute(
    OutputInterface $output,
    #[Argument('filename', 'File to process', required: true)]
    string $filename,
    #[Argument('format', 'Output format', required: false)]
    string $format = 'json'
): int
```

**Properties:**
- `name` (string) - Argument name
- `description` (string) - Help text
- `required` (bool) - Whether argument is mandatory (default: true)

### #[Option]

Declares a named option/flag (applied to method parameters):

```php
public function execute(
    OutputInterface $output,
    #[Option('force', 'f', 'Force operation', false)]
    bool $force = false,
    #[Option('timeout', 't', 'Timeout in seconds', 30)]
    int $timeout = 30
): int
```

**Properties:**
- `name` (string) - Long option name (--force)
- `shortcut` (string) - Short option (-f)
- `description` (string) - Help text
- `default` (mixed) - Default value if not provided

## Type Casting

Arguments and options are automatically cast to parameter types:

```php
#[ConsoleCommand('process')]
class ProcessCommand implements CommandInterface
{
    public function execute(
        OutputInterface $output,
        #[Argument('count')]
        int $count,                    // "42" → 42
        #[Option('enabled', 'e')]
        bool $enabled = false,         // "true" → true, "1" → true
        #[Option('ratio', 'r')]
        float $ratio = 1.5,            // "2.5" → 2.5
        #[Argument('name')]
        string $name                   // Remains string
    ): int {
        $output->info("Count: $count (int)");
        $output->info("Enabled: " . ($enabled ? 'yes' : 'no') . " (bool)");
        $output->info("Ratio: $ratio (float)");
        $output->info("Name: $name (string)");
        return 0;
    }
}
```

**Usage:**
```bash
php console process 42 --enabled --ratio=2.5 "MyName"
```

## Output Methods

The `OutputInterface` provides styled output:

```php
public function execute(OutputInterface $output): int
{
    // Plain text
    $output->write('Text without newline');
    $output->writeln('Text with newline');
    
    // Styled messages
    $output->success('Operation completed successfully');
    $output->error('Something went wrong');
    $output->warning('Be careful');
    $output->info('For your information');
    
    // Tables
    $output->table(
        ['Name', 'Email', 'Status'],
        [
            ['John', 'john@example.com', 'Active'],
            ['Jane', 'jane@example.com', 'Inactive']
        ]
    );
    
    return 0;
}
```

**Output:**
```
✓ Operation completed successfully
✗ Something went wrong
⚠ Be careful
ℹ For your information

| Name | Email             | Status   |
|------|-------------------|----------|
| John | john@example.com  | Active   |
| Jane | jane@example.com  | Inactive |
```

## Dependency Injection

Commands support constructor injection:

```php
#[ConsoleCommand('user:create')]
class CreateUserCommand implements CommandInterface
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Logger $logger
    ) {}

    public function execute(
        OutputInterface $output,
        #[Argument('email')]
        string $email,
        #[Argument('password')]
        string $password
    ): int {
        $user = $this->users->create($email, $password);
        $this->logger->info("User created: {$user->email}");
        $output->success("User created: {$user->email}");
        return 0;
    }
}
```

Dependencies are resolved from the container automatically.

## Exit Codes

Return integer exit codes to indicate success/failure:

```php
public function execute(OutputInterface $output): int
{
    try {
        // Do work...
        return 0; // Success
    } catch (\Exception $e) {
        $output->error($e->getMessage());
        return 1; // Error
    }
}
```

**Shell script usage:**
```bash
php console migrate
if [ $? -eq 0 ]; then
    echo "Success"
else
    echo "Failed"
fi
```

## Complete Example: Database Seed Command

```php
<?php

declare(strict_types=1);

namespace App\Commands;

use App\Repositories\UserRepository;
use Core\Attributes\Argument;
use Core\Attributes\ConsoleCommand;
use Core\Attributes\Option;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;

#[ConsoleCommand(
    name: 'db:seed',
    description: 'Seed the database with test data'
)]
class DatabaseSeedCommand implements CommandInterface
{
    public function __construct(
        private readonly UserRepository $users
    ) {}

    public function execute(
        OutputInterface $output,
        #[Argument('count', 'Number of users to create')]
        int $count = 10,
        #[Option('force', 'f', 'Skip confirmation')]
        bool $force = false
    ): int {
        if (!$force) {
            $output->warning("This will create {$count} test users.");
            $output->write("Continue? (yes/no): ");
            // In production, you'd read from STDIN
            return 0;
        }

        $output->info("Creating {$count} users...");
        $output->writeln('');

        $created = [];
        for ($i = 1; $i <= $count; $i++) {
            $email = "user{$i}@example.com";
            $password = bin2hex(random_bytes(8));
            
            $user = $this->users->create($email, $password);
            $created[] = [$user->id, $user->email, 'Created'];
            
            if ($i % 10 === 0) {
                $output->writeln("  Created {$i}/{$count} users...");
            }
        }

        $output->writeln('');
        $output->table(['ID', 'Email', 'Status'], $created);
        $output->success("Created {$count} users successfully!");
        
        return 0;
    }
}
```

**Register it:**
```php
// console
$console->registerCommands([
    MigrateCommand::class,
    MigrateRollbackCommand::class,
    DatabaseSeedCommand::class,
]);
```

**Usage:**
```bash
# Interactive (with confirmation)
php console db:seed 50

# Skip confirmation
php console db:seed 50 --force
php console db:seed 50 -f

# Default count (10)
php console db:seed --force
```

## Philosophy Compliance

The console system follows all framework principles:

✅ **No Magic**
- Commands explicitly registered (no scanning directories)
- Arguments/options explicitly declared via attributes
- No hidden global command registry

✅ **Strict Typing**
- All parameters typed (int, string, bool, float)
- Return type is `int` (exit code)
- OutputInterface prevents stringly-typed output

✅ **Attributes Over Configuration**
- `#[ConsoleCommand]` declares command where it's used
- `#[Argument]` and `#[Option]` on parameters they affect
- No routes/console.php file disconnected from code

✅ **Explicit Over Convenient**
- Must explicitly register each command
- Must explicitly declare each argument/option
- No "magic" argument parsing from method signature

✅ **Traceable**
- Command+Click on `MigrateCommand::class` in console script
- Command+Click on `#[ConsoleCommand]` attribute
- Clear execution path from `console` → `ConsoleApplication` → Command
