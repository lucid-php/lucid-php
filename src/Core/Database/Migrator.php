<?php

declare(strict_types=1);

namespace Core\Database;

class Migrator
{
    /**
     * @var list<string>
     */
    private array $migrationPaths;

    public function __construct(
        private Database $db,
        string|array $migrationsPath
    ) {
        $paths = is_string($migrationsPath) ? [$migrationsPath] : array_values($migrationsPath);

        if ($paths === []) {
            throw new \InvalidArgumentException('At least one migration path must be provided.');
        }

        $this->migrationPaths = array_values(array_unique(array_map(
            static fn (string $path): string => rtrim($path, '/'),
            $paths
        )));
    }

    /**
     * Apply all pending migrations as one new batch.
     *
     * @return array<int, string> The migration files that were applied, in order.
     */
    public function migrate(): array
    {
        $this->ensureMigrationsTable();

        $applied = $this->getAppliedMigrations();
        $manifest = $this->getMigrationManifest();
        $files = array_column($manifest, 'id');

        $toApply = array_values(array_diff($files, $applied));

        if ($toApply === []) {
            return [];
        }

        $batch = $this->nextBatch();

        foreach ($toApply as $migrationId) {
            $entry = $this->findManifestEntry($manifest, $migrationId);

            if ($entry === null) {
                throw new \RuntimeException("Migration entry not found for identifier: {$migrationId}");
            }

            $this->apply($entry, $batch);
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

        $manifestById = $this->manifestById($this->getMigrationManifest());

        foreach ($toRevert as $migrationId) {
            $this->revert($migrationId, $manifestById[$migrationId] ?? null);
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

        $rows = $this->db->query('SELECT migration, batch FROM migrations ORDER BY id ASC');
        $batchByFile = [];
        foreach ($rows as $row) {
            $batchByFile[$row['migration']] = (int) $row['batch'];
        }

        $status = [];
        foreach ($this->getMigrationManifest() as $entry) {
            $migrationId = $entry['id'];

            $status[] = [
                'migration' => $migrationId,
                'applied' => isset($batchByFile[$migrationId]),
                'batch' => $batchByFile[$migrationId] ?? null,
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
            $this->db->execute('ALTER TABLE migrations ADD COLUMN batch INT NOT NULL DEFAULT 1');
        } catch (\Throwable) {
            // Column already exists — nothing to do.
        }
    }

    /**
     * @return array<int, string> Applied migration filenames, in apply order.
     */
    private function getAppliedMigrations(): array
    {
        $rows = $this->db->query('SELECT migration FROM migrations ORDER BY id ASC');
        return array_column($rows, 'migration');
    }

    /**
     * The batch number for the next migrate() run (one greater than the max).
     */
    private function nextBatch(): int
    {
        $rows = $this->db->query('SELECT MAX(batch) AS max_batch FROM migrations');
        return ((int) ($rows[0]['max_batch'] ?? 0)) + 1;
    }

    /**
     * @param array{id: string, up_path: string, down_path: string} $entry
     */
    private function apply(array $entry, int $batch): void
    {
        $content = $this->readSql($entry['up_path']);

        // Split content by semicolons to handle multiple statements
        // This is needed for MySQL/MariaDB which don't support multi-query in PDO::exec by default
        $this->executeMultipleStatements($content);

        $this->db->execute(
            'INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)',
            ['migration' => $entry['id'], 'batch' => $batch]
        );
    }

    /**
     * @param array{id: string, up_path: string, down_path: string}|null $entry
     */
    private function revert(string $migrationId, ?array $entry): void
    {
        // If migration metadata is still present and has a .down.sql file, run it.
        if ($entry !== null && file_exists($entry['down_path'])) {
            $this->executeMultipleStatements($this->readSql($entry['down_path']));
        }

        $this->db->execute(
            'DELETE FROM migrations WHERE migration = :migration',
            ['migration' => $migrationId]
        );
    }

    /**
     * @return list<array{id: string, up_path: string, down_path: string}>
     */
    private function getMigrationManifest(): array
    {
        $entries = [];
        $seenIds = [];

        foreach ($this->migrationPaths as $index => $path) {
            $files = scandir($path);

            if ($files === false) {
                throw DatabaseException::migrationsPathUnreadable($path);
            }

            $upFiles = array_values(array_filter($files, static fn (string $file): bool => str_ends_with($file, '.up.sql')));
            sort($upFiles);

            foreach ($upFiles as $upFile) {
                $id = $this->buildMigrationIdentifier($index, $path, $upFile);

                if (isset($seenIds[$id])) {
                    throw new \RuntimeException("Duplicate migration identifier generated: {$id}");
                }

                $seenIds[$id] = true;

                $entries[] = [
                    'id' => $id,
                    'up_path' => $path . '/' . $upFile,
                    'down_path' => $path . '/' . str_replace('.up.sql', '.down.sql', $upFile),
                ];
            }
        }

        usort(
            $entries,
            static fn (array $a, array $b): int => $a['id'] <=> $b['id']
        );

        return $entries;
    }

    private function buildMigrationIdentifier(int $index, string $path, string $filename): string
    {
        if ($index === 0) {
            return $filename;
        }

        $canonicalPath = realpath($path) ?: $path;
        $pathHash = substr(sha1($canonicalPath), 0, 12);

        return $pathHash . '::' . $filename;
    }

    /**
     * @param list<array{id: string, up_path: string, down_path: string}> $manifest
     * @return array{id: string, up_path: string, down_path: string}|null
     */
    private function findManifestEntry(array $manifest, string $migrationId): ?array
    {
        foreach ($manifest as $entry) {
            if ($entry['id'] === $migrationId) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @param list<array{id: string, up_path: string, down_path: string}> $manifest
     * @return array<string, array{id: string, up_path: string, down_path: string}>
     */
    private function manifestById(array $manifest): array
    {
        $indexed = [];

        foreach ($manifest as $entry) {
            $indexed[$entry['id']] = $entry;
        }

        return $indexed;
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
            fn ($line) => !str_starts_with(trim($line), '--')
        );
        $cleaned = implode("\n", $lines);

        // Split into individual statements and run each non-empty one.
        $statements = array_filter(
            array_map('trim', explode(';', $cleaned)),
            fn ($stmt) => $stmt !== ''
        );

        foreach ($statements as $statement) {
            $this->db->execute($statement);
        }
    }
}
