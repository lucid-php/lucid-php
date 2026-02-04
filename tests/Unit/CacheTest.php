<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Cache\ArrayCache;
use Core\Cache\CacheException;
use Core\Cache\FileCache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/framework_cache_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($this->tempDir);
        }
    }

    // ===== ArrayCache Tests =====

    public function test_array_cache_set_and_get(): void
    {
        $cache = new ArrayCache();
        
        $cache->set('key1', 'value1', 60);
        
        $this->assertSame('value1', $cache->get('key1'));
    }

    public function test_array_cache_returns_default_when_key_missing(): void
    {
        $cache = new ArrayCache();
        
        $this->assertNull($cache->get('nonexistent'));
        $this->assertSame('default', $cache->get('nonexistent', 'default'));
    }

    public function test_array_cache_has(): void
    {
        $cache = new ArrayCache();
        
        $this->assertFalse($cache->has('key1'));
        
        $cache->set('key1', 'value1', 60);
        
        $this->assertTrue($cache->has('key1'));
    }

    public function test_array_cache_delete(): void
    {
        $cache = new ArrayCache();
        
        $cache->set('key1', 'value1', 60);
        $this->assertTrue($cache->has('key1'));
        
        $result = $cache->delete('key1');
        
        $this->assertTrue($result);
        $this->assertFalse($cache->has('key1'));
    }

    public function test_array_cache_delete_nonexistent_returns_false(): void
    {
        $cache = new ArrayCache();
        
        $result = $cache->delete('nonexistent');
        
        $this->assertFalse($result);
    }

    public function test_array_cache_clear(): void
    {
        $cache = new ArrayCache();
        
        $cache->set('key1', 'value1', 60);
        $cache->set('key2', 'value2', 60);
        
        $cache->clear();
        
        $this->assertFalse($cache->has('key1'));
        $this->assertFalse($cache->has('key2'));
    }

    public function test_array_cache_ttl_expiration(): void
    {
        $cache = new ArrayCache();
        
        $cache->set('key1', 'value1', 1);
        $this->assertTrue($cache->has('key1'));
        
        sleep(2);
        
        $this->assertFalse($cache->has('key1'));
        $this->assertNull($cache->get('key1'));
    }

    public function test_array_cache_set_multiple(): void
    {
        $cache = new ArrayCache();
        
        $cache->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ], 60);
        
        $this->assertSame('value1', $cache->get('key1'));
        $this->assertSame('value2', $cache->get('key2'));
        $this->assertSame('value3', $cache->get('key3'));
    }

    public function test_array_cache_get_multiple(): void
    {
        $cache = new ArrayCache();
        
        $cache->set('key1', 'value1', 60);
        $cache->set('key2', 'value2', 60);
        
        $results = $cache->getMultiple(['key1', 'key2', 'key3'], 'default');
        
        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'default',
        ], $results);
    }

    public function test_array_cache_delete_multiple(): void
    {
        $cache = new ArrayCache();
        
        $cache->set('key1', 'value1', 60);
        $cache->set('key2', 'value2', 60);
        $cache->set('key3', 'value3', 60);
        
        $cache->deleteMultiple(['key1', 'key3']);
        
        $this->assertFalse($cache->has('key1'));
        $this->assertTrue($cache->has('key2'));
        $this->assertFalse($cache->has('key3'));
    }

    public function test_array_cache_remember_returns_cached_value(): void
    {
        $cache = new ArrayCache();
        
        $cache->set('key1', 'cached_value', 60);
        
        $callbackExecuted = false;
        $result = $cache->remember('key1', function () use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'new_value';
        }, 60);
        
        $this->assertSame('cached_value', $result);
        $this->assertFalse($callbackExecuted);
    }

    public function test_array_cache_remember_executes_callback_on_miss(): void
    {
        $cache = new ArrayCache();
        
        $callbackExecuted = false;
        $result = $cache->remember('key1', function () use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'computed_value';
        }, 60);
        
        $this->assertSame('computed_value', $result);
        $this->assertTrue($callbackExecuted);
        $this->assertSame('computed_value', $cache->get('key1'));
    }

    public function test_array_cache_stores_various_types(): void
    {
        $cache = new ArrayCache();
        
        $cache->set('string', 'text', 60);
        $cache->set('int', 42, 60);
        $cache->set('float', 3.14, 60);
        $cache->set('bool', true, 60);
        $cache->set('array', ['a', 'b', 'c'], 60);
        $cache->set('object', (object)['foo' => 'bar'], 60);
        
        $this->assertSame('text', $cache->get('string'));
        $this->assertSame(42, $cache->get('int'));
        $this->assertSame(3.14, $cache->get('float'));
        $this->assertTrue($cache->get('bool'));
        $this->assertSame(['a', 'b', 'c'], $cache->get('array'));
        $this->assertEquals((object)['foo' => 'bar'], $cache->get('object'));
    }

    public function test_array_cache_count(): void
    {
        $cache = new ArrayCache();
        
        $this->assertSame(0, $cache->count());
        
        $cache->set('key1', 'value1', 60);
        $cache->set('key2', 'value2', 60);
        
        $this->assertSame(2, $cache->count());
    }

    public function test_array_cache_get_all_keys(): void
    {
        $cache = new ArrayCache();
        
        $cache->set('key1', 'value1', 60);
        $cache->set('key2', 'value2', 60);
        
        $keys = $cache->getAllKeys();
        
        $this->assertContains('key1', $keys);
        $this->assertContains('key2', $keys);
    }

    public function test_array_cache_throws_on_invalid_ttl(): void
    {
        $cache = new ArrayCache();
        
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('Invalid TTL value [0]. Must be positive integer.');
        
        $cache->set('key1', 'value1', 0);
    }

    // ===== FileCache Tests =====

    public function test_file_cache_creates_directory(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $this->assertDirectoryExists($this->tempDir);
    }

    public function test_file_cache_set_and_get(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $cache->set('key1', 'value1', 60);
        
        $this->assertSame('value1', $cache->get('key1'));
    }

    public function test_file_cache_persists_across_instances(): void
    {
        $cache1 = new FileCache($this->tempDir);
        $cache1->set('key1', 'persistent_value', 60);
        
        $cache2 = new FileCache($this->tempDir);
        
        $this->assertSame('persistent_value', $cache2->get('key1'));
    }

    public function test_file_cache_has(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $this->assertFalse($cache->has('key1'));
        
        $cache->set('key1', 'value1', 60);
        
        $this->assertTrue($cache->has('key1'));
    }

    public function test_file_cache_delete(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $cache->set('key1', 'value1', 60);
        $this->assertTrue($cache->has('key1'));
        
        $result = $cache->delete('key1');
        
        $this->assertTrue($result);
        $this->assertFalse($cache->has('key1'));
    }

    public function test_file_cache_clear(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $cache->set('key1', 'value1', 60);
        $cache->set('key2', 'value2', 60);
        
        $cache->clear();
        
        $this->assertFalse($cache->has('key1'));
        $this->assertFalse($cache->has('key2'));
    }

    public function test_file_cache_ttl_expiration(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $cache->set('key1', 'value1', 1);
        $this->assertTrue($cache->has('key1'));
        
        sleep(2);
        
        $this->assertFalse($cache->has('key1'));
        $this->assertNull($cache->get('key1'));
    }

    public function test_file_cache_stores_various_types(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $cache->set('string', 'text', 60);
        $cache->set('int', 42, 60);
        $cache->set('array', ['a', 'b', 'c'], 60);
        $cache->set('object', (object)['foo' => 'bar'], 60);
        
        $this->assertSame('text', $cache->get('string'));
        $this->assertSame(42, $cache->get('int'));
        $this->assertSame(['a', 'b', 'c'], $cache->get('array'));
        $this->assertEquals((object)['foo' => 'bar'], $cache->get('object'));
    }

    public function test_file_cache_remember(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $result = $cache->remember('expensive_key', function () {
            return 'expensive_computation';
        }, 60);
        
        $this->assertSame('expensive_computation', $result);
        $this->assertSame('expensive_computation', $cache->get('expensive_key'));
    }

    public function test_file_cache_set_multiple(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $cache->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
        ], 60);
        
        $this->assertSame('value1', $cache->get('key1'));
        $this->assertSame('value2', $cache->get('key2'));
    }

    public function test_file_cache_get_multiple(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $cache->set('key1', 'value1', 60);
        $cache->set('key2', 'value2', 60);
        
        $results = $cache->getMultiple(['key1', 'key2', 'key3'], 'default');
        
        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'default',
        ], $results);
    }

    public function test_file_cache_throws_on_invalid_ttl(): void
    {
        $cache = new FileCache($this->tempDir);
        
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('Invalid TTL value [-1]. Must be positive integer.');
        
        $cache->set('key1', 'value1', -1);
    }
}
