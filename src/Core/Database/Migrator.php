<?php

declare(strict_types=1);

namespace Core\Database;

class Migrator
{
    public function __construct(
        private Database $db,
        private string $migrationsPath
    ) {}

    public function migrate(): void
    {
        $this->ensureMigrationsTable();

        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();
        
        $toApply = array_diff($files, $applied);

        if (empty($toApply)) {
            echo "Nothing to migrate.\n";
            return;
        }

        foreach ($toApply as $file) {
            echo "Migrating: $file\n";
            $this->apply($file);
            echo "Migrated:  $file\n";
        }
    }

    public function rollback(): void
    {
        $this->ensureMigrationsTable();

        $applied = $this->getAppliedMigrations();
        
        if (empty($applied)) {
            echo "Nothing to rollback.\n";
            return;
        }

        // Rollback the last one
        $lastMigration = end($applied);
        
        echo "Rolling back: $lastMigration\n";
        $this->revert($lastMigration);
        echo "Rolled back:  $lastMigration\n";
    }

    private function ensureMigrationsTable(): void
    {
        $driver = $this->db->getDriverName();
        
        $idColumn = match ($driver) {
            'sqlite' => 'id INTEGER PRIMARY KEY AUTOINCREMENT',
            'mysql' => 'id INT AUTO_INCREMENT PRIMARY KEY',
            default => 'id INT PRIMARY KEY' // Fallback
        };

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS migrations (
                $idColumn,
                migration VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    private function getAppliedMigrations(): array
    {
        $rows = $this->db->query("SELECT migration FROM migrations");
        return array_column($rows, 'migration');
    }

    private function getMigrationFiles(): array
    {
        $files = scandir($this->migrationsPath);
        $filtered = array_filter($files, fn($f) => str_ends_with($f, '.up.sql'));
        return array_values($filtered); // Re-index
    }

    private function apply(string $file): void
    {
        $content = file_get_contents($this->migrationsPath . '/' . $file);
        
        // Split content by semicolons to handle multiple statements
        // This is needed for MySQL/MariaDB which don't support multi-query in PDO::exec by default
        $this->executeMultipleStatements($content);
        
        $this->db->execute(
            "INSERT INTO migrations (migration) VALUES (:migration)",
            ['migration' => $file]
        );
    }

    private function revert(string $file): void
    {
        // Convert 'xxxx.up.sql' to 'xxxx.down.sql'
        $downFile = str_replace('.up.sql', '.down.sql', $file);
        $fullPath = $this->migrationsPath . '/' . $downFile;

        if (!file_exists($fullPath)) {
            echo "Warning: No down file found for $file (looked for $downFile). Skipping SQL execution, but removing record.\n";
        } else {
            $content = file_get_contents($fullPath);
            $this->executeMultipleStatements($content);
        }

        $this->db->execute(
            "DELETE FROM migrations WHERE migration = :migration",
            ['migration' => $file]
        );
    }

    private function executeMultipleStatements(string $sql): void
    {
        // Split SQL by semicolons, handling comments and empty lines
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
        );

        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $this->db->execute($statement);
            }
        }
    }
}
