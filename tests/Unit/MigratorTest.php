<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Database\Database;
use Core\Database\Migrator;
use PHPUnit\Framework\TestCase;

class MigratorTest extends TestCase
{
    private Database $db;
    private string $defaultMigrationsPath;
    /**
     * @var list<string>
     */
    private array $createdPaths = [];

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');
        $this->defaultMigrationsPath = $this->createMigrationPath('default');
    }

    protected function tearDown(): void
    {
        foreach ($this->createdPaths as $path) {
            foreach (glob($path . '/*') ?: [] as $file) {
                unlink($file);
            }

            rmdir($path);
        }
    }

    public function testMigrateCreatesTable(): void
    {
        // Create a test migration
        file_put_contents(
            $this->defaultMigrationsPath . '/001_create_test.up.sql',
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY)'
        );

        $migrator = new Migrator($this->db, $this->defaultMigrationsPath);

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
            $this->defaultMigrationsPath . '/001_create_test.up.sql',
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY)'
        );

        $migrator = new Migrator($this->db, $this->defaultMigrationsPath);

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
            $this->defaultMigrationsPath . '/001_create_test.up.sql',
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY)'
        );
        file_put_contents(
            $this->defaultMigrationsPath . '/001_create_test.down.sql',
            'DROP TABLE test_table'
        );

        $migrator = new Migrator($this->db, $this->defaultMigrationsPath);

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

        $migrator = new Migrator($this->db, $this->defaultMigrationsPath);
        $applied = $migrator->migrate();

        $this->assertSame(['001_a.up.sql', '002_b.up.sql'], $applied);

        // Running again applies nothing.
        $this->assertSame([], $migrator->migrate());
    }

    public function testStatusReportsAppliedAndPending(): void
    {
        $this->writeMigration('001_a', 'CREATE TABLE a (id INTEGER PRIMARY KEY)');
        $this->writeMigration('002_b', 'CREATE TABLE b (id INTEGER PRIMARY KEY)');

        $migrator = new Migrator($this->db, $this->defaultMigrationsPath);

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

        $migrator = new Migrator($this->db, $this->defaultMigrationsPath);
        $migrator->migrate();

        $reverted = $migrator->rollback(2);

        $this->assertSame(['002_b.up.sql', '001_a.up.sql'], $reverted, 'newest reverted first');

        $tables = $this->db->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name IN ('a','b')"
        );
        $this->assertCount(0, $tables);
    }

    public function testMigrateSupportsMultipleExplicitPaths(): void
    {
        $additionalPath = $this->createMigrationPath('additional');
        $this->writeMigration('001_base', 'CREATE TABLE base_items (id INTEGER PRIMARY KEY)');
        $this->writeMigration(
            name: '001_extra',
            up: 'CREATE TABLE extra_items (id INTEGER PRIMARY KEY)',
            path: $additionalPath
        );

        $migrator = new Migrator($this->db, [$this->defaultMigrationsPath, $additionalPath]);
        $applied = $migrator->migrate();

        $this->assertCount(2, $applied);
        $this->assertContains('001_base.up.sql', $applied);
        $this->assertCount(
            1,
            array_filter(
                $applied,
                static fn (string $migration): bool => preg_match('/^[a-f0-9]{12}::001_extra\.up\.sql$/', $migration) === 1
            )
        );

        $status = $migrator->status();
        $this->assertCount(2, $status);
        $this->assertTrue($status[0]['applied']);
        $this->assertTrue($status[1]['applied']);
    }

    public function testMigrateUsesUniqueIdentifiersForSameFilenameAcrossAdditionalPaths(): void
    {
        $extraA = $this->createMigrationPath('extra_a');
        $extraB = $this->createMigrationPath('extra_b');

        $this->writeMigration('001_shared', 'CREATE TABLE default_shared (id INTEGER PRIMARY KEY)');
        $this->writeMigration('001_shared', 'CREATE TABLE extra_shared_a (id INTEGER PRIMARY KEY)', path: $extraA);
        $this->writeMigration('001_shared', 'CREATE TABLE extra_shared_b (id INTEGER PRIMARY KEY)', path: $extraB);

        $migrator = new Migrator($this->db, [$this->defaultMigrationsPath, $extraA, $extraB]);
        $applied = $migrator->migrate();

        $this->assertCount(3, $applied);
        $this->assertContains('001_shared.up.sql', $applied);

        $prefixed = array_values(array_filter(
            $applied,
            static fn (string $migration): bool => preg_match('/^[a-f0-9]{12}::001_shared\.up\.sql$/', $migration) === 1
        ));

        $this->assertCount(2, $prefixed);
        $this->assertNotSame($prefixed[0], $prefixed[1]);
    }

    public function testRollbackSupportsDownMigrationsFromAdditionalPath(): void
    {
        $additionalPath = $this->createMigrationPath('additional_down');
        $this->writeMigration(
            name: '001_extra',
            up: 'CREATE TABLE extra_items (id INTEGER PRIMARY KEY)',
            down: 'DROP TABLE extra_items',
            path: $additionalPath
        );

        $migrator = new Migrator($this->db, [$this->defaultMigrationsPath, $additionalPath]);
        $migrator->migrate();
        $reverted = $migrator->rollback();

        $this->assertCount(1, $reverted);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{12}::001_extra\.up\.sql$/', $reverted[0]);

        $tables = $this->db->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='extra_items'"
        );
        $this->assertCount(0, $tables);
    }

    private function writeMigration(
        string $name,
        string $up,
        ?string $down = null,
        ?string $path = null
    ): void {
        $targetPath = $path ?? $this->defaultMigrationsPath;

        file_put_contents("{$targetPath}/{$name}.up.sql", $up);
        if ($down !== null) {
            file_put_contents("{$targetPath}/{$name}.down.sql", $down);
        }
    }

    private function createMigrationPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/test_migrations_' . $suffix . '_' . uniqid();
        mkdir($path);
        $this->createdPaths[] = $path;

        return $path;
    }
}
