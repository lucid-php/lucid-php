<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private string $testConfigPath;

    protected function setUp(): void
    {
        // Create temporary config directory
        $this->testConfigPath = sys_get_temp_dir() . '/test_config_' . uniqid();
        mkdir($this->testConfigPath);

        // Create test config files
        file_put_contents(
            $this->testConfigPath . '/test.php',
            "<?php\nreturn ['key' => 'value', 'nested' => ['item' => 'data']];"
        );

        file_put_contents(
            $this->testConfigPath . '/database.php',
            "<?php\nreturn ['driver' => 'mysql', 'mysql' => ['host' => 'localhost', 'port' => 3306]];"
        );
    }

    protected function tearDown(): void
    {
        // Clean up test files
        array_map('unlink', glob($this->testConfigPath . '/*'));
        rmdir($this->testConfigPath);
    }

    public function test_load_returns_config_array(): void
    {
        $config = new Config($this->testConfigPath);
        $result = $config->load('test');

        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
    }

    public function test_load_caches_config(): void
    {
        $config = new Config($this->testConfigPath);
        
        $first = $config->load('test');
        $second = $config->load('test');

        $this->assertSame($first, $second);
    }

    public function test_get_retrieves_nested_values(): void
    {
        $config = new Config($this->testConfigPath);

        $this->assertEquals('value', $config->get('test.key'));
        $this->assertEquals('data', $config->get('test.nested.item'));
        $this->assertEquals('localhost', $config->get('database.mysql.host'));
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $config = new Config($this->testConfigPath);

        $this->assertNull($config->get('test.nonexistent'));
        $this->assertEquals('default', $config->get('test.nonexistent', 'default'));
    }

    public function test_has_returns_true_for_existing_key(): void
    {
        $config = new Config($this->testConfigPath);

        $this->assertTrue($config->has('test.key'));
        $this->assertTrue($config->has('test.nested.item'));
        $this->assertTrue($config->has('database.mysql.host'));
    }

    public function test_has_returns_false_for_missing_key(): void
    {
        $config = new Config($this->testConfigPath);

        $this->assertFalse($config->has('test.nonexistent'));
        $this->assertFalse($config->has('nonexistent.key'));
    }

    public function test_all_returns_entire_config_file(): void
    {
        $config = new Config($this->testConfigPath);

        $all = $config->all('test');

        $this->assertIsArray($all);
        $this->assertArrayHasKey('key', $all);
        $this->assertArrayHasKey('nested', $all);
    }

    public function test_throws_exception_for_missing_config_directory(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config directory not found');

        new Config('/nonexistent/path');
    }

    public function test_throws_exception_for_missing_config_file(): void
    {
        $config = new Config($this->testConfigPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config file not found');

        $config->load('nonexistent');
    }

    public function test_throws_exception_for_invalid_config_file(): void
    {
        file_put_contents(
            $this->testConfigPath . '/invalid.php',
            "<?php\nreturn 'not an array';"
        );

        $config = new Config($this->testConfigPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config file must return an array');

        $config->load('invalid');
    }
}
