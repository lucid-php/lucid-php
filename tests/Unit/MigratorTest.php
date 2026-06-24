<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Database\Database;
use Core\Database\Migrator;
use PHPUnit\Framework\TestCase;

class MigratorTest extends TestCase
{
    private Database $db;
    private string $migrationsPath;

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');
        $this->migrationsPath = sys_get_temp_dir() . '/test_migrations_' . uniqid();
        mkdir($this->migrationsPath);
    }

    protected function tearDown(): void
    {
        // Clean up migration files
        array_map('unlink', glob($this->migrationsPath . '/*'));
        rmdir($this->migrationsPath);
    }

    public function testMigrateCreatesTable(): void
    {
        // Create a test migration
        file_put_contents(
            $this->migrationsPath . '/001_create_test.up.sql',
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY)'
        );

        $migrator = new Migrator($this->db, $this->migrationsPath);

        ob_start();
        $migrator->migrate();
        ob_end_clean();

        // Verify table exists
        $tables = $this->db->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'"
        );

        $this->assertCount(1, $tables);
    }

    public function testMigrateTracksAppliedMigrations(): void
    {
        file_put_contents(
            $this->migrationsPath . '/001_create_test.up.sql',
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY)'
        );

        $migrator = new Migrator($this->db, $this->migrationsPath);

        ob_start();
        $migrator->migrate();
        ob_end_clean();

        // Check migrations table
        $applied = $this->db->query('SELECT migration FROM migrations');

        $this->assertCount(1, $applied);
        $this->assertSame('001_create_test.up.sql', array_first($applied)['migration']);
    }

    public function testRollbackDropsTable(): void
    {
        file_put_contents(
            $this->migrationsPath . '/001_create_test.up.sql',
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY)'
        );
        file_put_contents(
            $this->migrationsPath . '/001_create_test.down.sql',
            'DROP TABLE test_table'
        );

        $migrator = new Migrator($this->db, $this->migrationsPath);

        $migrator->migrate();
        $migrator->rollback();

        // Verify table doesn't exist
        $tables = $this->db->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'"
        );

        $this->assertCount(0, $tables);
    }

    public function testMigrateReturnsAppliedFilenames(): void
    {
        $this->writeMigration('001_a', 'CREATE TABLE a (id INTEGER PRIMARY KEY)');
        $this->writeMigration('002_b', 'CREATE TABLE b (id INTEGER PRIMARY KEY)');

        $migrator = new Migrator($this->db, $this->migrationsPath);
        $applied = $migrator->migrate();

        $this->assertSame(['001_a.up.sql', '002_b.up.sql'], $applied);

        // Running again applies nothing.
        $this->assertSame([], $migrator->migrate());
    }

    public function testStatusReportsAppliedAndPending(): void
    {
        $this->writeMigration('001_a', 'CREATE TABLE a (id INTEGER PRIMARY KEY)');
        $this->writeMigration('002_b', 'CREATE TABLE b (id INTEGER PRIMARY KEY)');

        $migrator = new Migrator($this->db, $this->migrationsPath);

        $before = $migrator->status();
        $this->assertFalse($before[0]['applied']);
        $this->assertFalse($before[1]['applied']);

        $migrator->migrate();

        $after = $migrator->status();
        $this->assertTrue($after[0]['applied']);
        $this->assertTrue($after[1]['applied']);
        $this->assertSame(1, $after[0]['batch']);
    }

    public function testMultiStepRollbackRevertsInReverseOrder(): void
    {
        $this->writeMigration('001_a', 'CREATE TABLE a (id INTEGER PRIMARY KEY)', 'DROP TABLE a');
        $this->writeMigration('002_b', 'CREATE TABLE b (id INTEGER PRIMARY KEY)', 'DROP TABLE b');

        $migrator = new Migrator($this->db, $this->migrationsPath);
        $migrator->migrate();

        $reverted = $migrator->rollback(2);

        $this->assertSame(['002_b.up.sql', '001_a.up.sql'], $reverted, 'newest reverted first');

        $tables = $this->db->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name IN ('a','b')"
        );
        $this->assertCount(0, $tables);
    }

    private function writeMigration(string $name, string $up, ?string $down = null): void
    {
        file_put_contents("{$this->migrationsPath}/{$name}.up.sql", $up);
        if ($down !== null) {
            file_put_contents("{$this->migrationsPath}/{$name}.down.sql", $down);
        }
    }
}
