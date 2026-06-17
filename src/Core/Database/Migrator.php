<?php

declare(strict_types=1);

namespace Core\Database;

class Migrator
{
    public function __construct(
        private Database $db,
        private string $migrationsPath
    ) {}

    /**
     * Apply all pending migrations as one new batch.
     *
     * @return array<int, string> The migration files that were applied, in order.
     */
    public function migrate(): array
    {
        $this->ensureMigrationsTable();

        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();

        $toApply = array_values(array_diff($files, $applied));

        if ($toApply === []) {
            return [];
        }

        $batch = $this->nextBatch();

        foreach ($toApply as $file) {
            $this->apply($file, $batch);
        }

        return $toApply;
    }

    /**
     * Roll back the most recently applied migrations.
     *
     * @param int $steps How many migrations to revert (most recent first).
     * @return array<int, string> The migration files that were reverted, in order.
     */
    public function rollback(int $steps = 1): array
    {
        $this->ensureMigrationsTable();

        $applied = $this->getAppliedMigrations();

        if ($applied === []) {
            return [];
        }

        $toRevert = array_reverse(array_slice($applied, -max(1, $steps)));

        foreach ($toRevert as $file) {
            $this->revert($file);
        }

        return $toRevert;
    }

    /**
     * Report every migration file and whether/when it was applied.
     *
     * @return array<int, array{migration: string, applied: bool, batch: int|null}>
     */
    public function status(): array
    {
        $this->ensureMigrationsTable();

        $rows = $this->db->query("SELECT migration, batch FROM migrations ORDER BY id ASC");
        $batchByFile = [];
        foreach ($rows as $row) {
            $batchByFile[$row['migration']] = (int) $row['batch'];
        }

        $status = [];
        foreach ($this->getMigrationFiles() as $file) {
            $status[] = [
                'migration' => $file,
                'applied' => isset($batchByFile[$file]),
                'batch' => $batchByFile[$file] ?? null,
            ];
        }

        return $status;
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
                batch INT NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Backfill the batch column on migrations tables created before it
        // existed. ALTER ... ADD COLUMN is portable (SQLite + MySQL); it errors
        // only when the column is already present, which we can safely ignore.
        try {
            $this->db->execute("ALTER TABLE migrations ADD COLUMN batch INT NOT NULL DEFAULT 1");
        } catch (\Throwable) {
            // Column already exists — nothing to do.
        }
    }

    /**
     * @return array<int, string> Applied migration filenames, in apply order.
     */
    private function getAppliedMigrations(): array
    {
        $rows = $this->db->query("SELECT migration FROM migrations ORDER BY id ASC");
        return array_column($rows, 'migration');
    }

    /**
     * @return array<int, string> Migration .up.sql filenames, sorted.
     */
    private function getMigrationFiles(): array
    {
        $files = scandir($this->migrationsPath);

        if ($files === false) {
            throw DatabaseException::migrationsPathUnreadable($this->migrationsPath);
        }

        $filtered = array_values(array_filter($files, fn($f) => str_ends_with($f, '.up.sql')));
        sort($filtered); // deterministic ordering by filename (001_, 002_, ...)

        return $filtered;
    }

    /**
     * The batch number for the next migrate() run (one greater than the max).
     */
    private function nextBatch(): int
    {
        $rows = $this->db->query("SELECT MAX(batch) AS max_batch FROM migrations");
        return ((int) ($rows[0]['max_batch'] ?? 0)) + 1;
    }

    private function apply(string $file, int $batch): void
    {
        $content = $this->readSql($this->migrationsPath . '/' . $file);

        // Split content by semicolons to handle multiple statements
        // This is needed for MySQL/MariaDB which don't support multi-query in PDO::exec by default
        $this->executeMultipleStatements($content);

        $this->db->execute(
            "INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)",
            ['migration' => $file, 'batch' => $batch]
        );
    }

    private function revert(string $file): void
    {
        // Convert 'xxxx.up.sql' to 'xxxx.down.sql'
        $downFile = str_replace('.up.sql', '.down.sql', $file);
        $fullPath = $this->migrationsPath . '/' . $downFile;

        // If a down file exists, run it; either way remove the record so the
        // migration is considered rolled back.
        if (file_exists($fullPath)) {
            $this->executeMultipleStatements($this->readSql($fullPath));
        }

        $this->db->execute(
            "DELETE FROM migrations WHERE migration = :migration",
            ['migration' => $file]
        );
    }

    private function readSql(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw DatabaseException::migrationsPathUnreadable($path);
        }

        return $content;
    }

    private function executeMultipleStatements(string $sql): void
    {
        // Strip full-line SQL comments first. Splitting on ';' alone would let a
        // leading "-- comment" line swallow the statement that follows it.
        $lines = array_filter(
            explode("\n", $sql),
            fn($line) => !str_starts_with(trim($line), '--')
        );
        $cleaned = implode("\n", $lines);

        // Split into individual statements and run each non-empty one.
        $statements = array_filter(
            array_map('trim', explode(';', $cleaned)),
            fn($stmt) => $stmt !== ''
        );

        foreach ($statements as $statement) {
            $this->db->execute($statement);
        }
    }
}
