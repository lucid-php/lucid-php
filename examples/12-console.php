<?php

declare(strict_types=1);

/**
 * Example 12: Console Commands
 * 
 * Demonstrates:
 * - Creating console commands
 * - Command arguments and options
 * - Output formatting
 * - Console application setup
 * 
 * Note: This example shows command structure.
 * Run actual commands with: php console <command>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Console\ConsoleApplication;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Attributes\ConsoleCommand;
use Core\Attributes\Argument;
use Core\Attributes\Option;

echo "Console Commands Examples:\n";
echo "==========================\n\n";

// ===========================
// Example 1: Simple Command
// ===========================

echo "=== Example 1: Simple Command ===\n\n";

#[ConsoleCommand(
    name: 'greet',
    description: 'Greet a user by name'
)]
class GreetCommand implements CommandInterface
{
    public function execute(
        OutputInterface $output,
        #[Argument('name', 'The name to greet', required: false)]
        string $name = 'World'
    ): int {
        $output->writeln("Hello, $name!");
        $output->success("Greeting completed");
        
        return 0; // Success
    }
}

echo "Command structure:\n";
echo "  Uses #[ConsoleCommand] attribute for metadata\n";
echo "  Arguments injected with #[Argument] attribute\n";
echo "  Usage: php console greet [name]\n";
echo "  Example: php console greet Magnus\n";
echo "  Output: Hello, Magnus!\n\n";

// ===========================
// Example 2: Command with Arguments
// ===========================

echo "=== Example 2: Command with Arguments ===\n\n";

#[ConsoleCommand(
    name: 'user:create',
    description: 'Create a new user'
)]
class CreateUserCommand implements CommandInterface
{
    public function execute(
        OutputInterface $output,
        #[Argument('name', 'User full name')]
        string $name,
        #[Argument('email', 'User email address')]
        string $email
    ): int {
        $output->writeln("Creating user...");
        $output->writeln("  Name: $name");
        $output->writeln("  Email: $email");
        
        // Simulate user creation
        sleep(1);
        
        $output->success("User created successfully!");
        
        return 0;
    }
}

echo "Command: user:create\n";
echo "Arguments defined with #[Argument] attribute:\n";
echo "  name  - User's full name (required)\n";
echo "  email - User's email address (required)\n";
echo "\nUsage:\n";
echo "  php console user:create 'John Doe' john@example.com\n\n";

// ===========================
// Example 3: Command with Options
// ===========================

echo "=== Example 3: Command with Options ===\n\n";

#[ConsoleCommand(
    name: 'db:seed',
    description: 'Seed the database with test data'
)]
class DatabaseSeedCommand implements CommandInterface
{
    public function execute(
        OutputInterface $output,
        #[Option('count', 'c', 'Number of records to create', 10)]
        int $count = 10,
        #[Option('force', 'f', 'Confirm execution', false)]
        bool $force = false
    ): int {
        if (!$force) {
            $output->warning('This will add test data to the database');
            $output->writeln('Use --force to confirm');
            return 1;
        }
        
        $output->writeln("Seeding database with $count records...");
        
        for ($i = 1; $i <= $count; $i++) {
            echo "  [$i/$count] Creating record $i\n";
            usleep(50000); // 0.05 seconds
        }
        
        $output->success("Database seeded with $count records");
        
        return 0;
    }
}

echo "Command: db:seed\n";
echo "Options defined with #[Option] attribute:\n";
echo "  --count=N - Number of records to create (default: 10)\n";
echo "  --force   - Confirm execution\n";
echo "\nUsage:\n";
echo "  php console db:seed --force --count=50\n\n";

// ===========================
// Example 4: Output Formatting
// ===========================

echo "=== Example 4: Output Formatting ===\n\n";

#[ConsoleCommand(
    name: 'status',
    description: 'Show system status with formatted output'
)]
class StatusCommand implements CommandInterface
{
    public function execute(OutputInterface $output): int
    {
        $output->writeln("System Status Report");
        $output->writeln("===================\n");
        
        // Success message
        $output->success("✓ Database: Connected");
        
        // Info message
        $output->info("ℹ Cache: 1,234 items");
        
        // Warning message
        $output->warning("⚠ Disk Space: 85% used");
        
        // Error message
        $output->error("✗ Queue Worker: Not running");
        
        // Plain text
        $output->writeln("\nUse 'php console help' for more commands");
        
        return 0;
    }
}

echo "Output methods:\n";
echo "  \$output->writeln('text')  - Plain text\n";
echo "  \$output->success('text')  - Green success message\n";
echo "  \$output->error('text')    - Red error message\n";
echo "  \$output->info('text')     - Blue info message\n";
echo "  \$output->warning('text')  - Yellow warning message\n\n";

// ===========================
// Example 5: Database Migration Command
// ===========================

echo "=== Example 5: Database Migration Command ===\n\n";

#[ConsoleCommand(
    name: 'migrate',
    description: 'Run database migrations'
)]
class MigrateCommand implements CommandInterface
{
    public function execute(OutputInterface $output): int
    {
        $migrations = [
            '001_create_users_table',
            '002_create_posts_table',
            '003_add_user_roles',
        ];
        
        $output->writeln("Running migrations...\n");
        
        foreach ($migrations as $migration) {
            $output->write("  Migrating: $migration");
            
            // Simulate migration
            sleep(1);
            
            $output->success(" ✓");
        }
        
        $output->writeln("");
        $output->success("All migrations completed successfully");
        
        return 0;
    }
}

echo "Command: migrate\n";
echo "Description: Run pending database migrations\n";
echo "Output: Progress indicator for each migration\n\n";

// ===========================
// Example 6: Cache Clear Command
// ===========================

echo "=== Example 6: Cache Management ===\n\n";

#[ConsoleCommand(
    name: 'cache:clear',
    description: 'Clear application cache'
)]
class CacheClearCommand implements CommandInterface
{
    public function execute(OutputInterface $output): int
    {
        $cacheDir = __DIR__ . '/../storage/cache';
        
        if (!is_dir($cacheDir)) {
            $output->warning('Cache directory does not exist');
            return 0;
        }
        
        $output->writeln("Clearing cache...");
        
        $files = glob($cacheDir . '/*');
        $count = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }
        
        $output->success("Cleared $count cache files");
        
        return 0;
    }
}

echo "Command: cache:clear\n";
echo "Description: Delete all cached files\n";
echo "Example output: 'Cleared 156 cache files'\n\n";

// ===========================
// Example 7: Console Application Setup
// ===========================

echo "=== Example 7: Console Application Setup ===\n\n";

echo "Create a console application in your 'console' file:\n\n";
echo "#!/usr/bin/env php\n";
echo "<?php\n\n";
echo "require __DIR__ . '/vendor/autoload.php';\n\n";
echo "use Core\\Console\\ConsoleApplication;\n\n";
echo "\$app = new ConsoleApplication();\n\n";
echo "// Register commands\n";
echo "\$app->addCommand(new GreetCommand());\n";
echo "\$app->addCommand(new CreateUserCommand());\n";
echo "\$app->addCommand(new MigrateCommand());\n";
echo "\$app->addCommand(new CacheClearCommand());\n\n";
echo "// Run application\n";
echo "\$exitCode = \$app->run();\n";
echo "exit(\$exitCode);\n\n";

// ===========================
// Example 8: Return Codes
// ===========================

echo "=== Example 8: Exit Codes ===\n\n";

echo "Commands should return proper exit codes:\n\n";
echo "  0 - Success\n";
echo "  1 - General error\n";
echo "  2 - Invalid usage\n";
echo "  > 0 - Specific error codes\n\n";

echo "Example:\n";
echo "  #[ConsoleCommand(name: 'greet', description: 'Greet a user')]\n";
echo "  class GreetCommand implements CommandInterface\n";
echo "  {\n";
echo "      public function execute(\n";
echo "          OutputInterface \$output,\n";
echo "          #[Argument('name', 'Name to greet', required: false)]\n";
echo "          string \$name = 'World'\n";
echo "      ): int {\n";
echo "          \$output->success(\"Hello, \$name!\");\n";
echo "          return 0; // Success\n";
echo "      }\n";
echo "  }\n\n";

// ===========================
// Example 9: Built-in Commands
// ===========================

echo "=== Example 9: Built-in Framework Commands ===\n\n";

echo "The framework includes these built-in commands:\n\n";

echo "Database:\n";
echo "  php console migrate              - Run migrations\n";
echo "  php console migrate:rollback     - Rollback last migration\n";
echo "  php console db:seed              - Seed database\n\n";

echo "Queue:\n";
echo "  php console queue:work           - Process queue jobs\n";
echo "  php console queue:failed         - List failed jobs\n";
echo "  php console queue:retry <id>     - Retry failed job\n\n";

echo "Cache:\n";
echo "  php console cache:clear          - Clear cache\n\n";

echo "Scheduler:\n";
echo "  php console schedule:run         - Run due scheduled tasks\n";
echo "  php console schedule:list        - List scheduled tasks\n\n";

// ===========================
// Best Practices
// ===========================

echo "=== Best Practices ===\n\n";

echo "1. Use descriptive command names\n";
echo "   ✓ user:create, cache:clear\n";
echo "   ✗ create, clear\n\n";

echo "2. Provide helpful descriptions\n";
echo "   ✓ Clear implementation that explains what the command does\n";
echo "   ✗ Vague or missing descriptions\n\n";

echo "3. Validate input early\n";
echo "   ✓ Check required arguments at the start\n";
echo "   ✓ Show usage information on invalid input\n\n";

echo "4. Give feedback during execution\n";
echo "   ✓ Progress indicators for long operations\n";
echo "   ✓ Success/error messages\n\n";

echo "5. Return proper exit codes\n";
echo "   ✓ 0 for success, > 0 for errors\n";
echo "   ✓ Allows script chaining and error detection\n\n";

echo "6. Keep commands focused\n";
echo "   ✓ One command = one responsibility\n";
echo "   ✗ Don't create mega-commands that do everything\n\n";
