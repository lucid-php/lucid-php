<?php

declare(strict_types=1);

/**
 * Example 9: Cache System
 * 
 * Demonstrates:
 * - Storing and retrieving cached data
 * - Cache drivers (File, Array)
 * - Cache expiration (TTL)
 * - Cache tags
 * - Remember pattern
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Cache\FileCache;
use Core\Cache\ArrayCache;

echo "Cache System Examples:\n";
echo "=====================\n\n";

// ===========================
// Example 1: Basic Cache Operations
// ===========================

echo "=== Example 1: Basic Cache Operations ===\n\n";

$cache = new FileCache(__DIR__ . '/../storage/cache');

// Store value
$cache->set('user:123', [
    'id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com'
], 3600); // Expires in 1 hour

echo "✓ Stored user in cache\n";

// Retrieve value
$user = $cache->get('user:123');
echo "✓ Retrieved user: {$user['name']}\n";

// Check if exists
if ($cache->has('user:123')) {
    echo "✓ Cache key exists\n";
}

// Delete value
$cache->delete('user:123');
echo "✓ Deleted user from cache\n\n";

// ===========================
// Example 2: Cache with Default Values
// ===========================

echo "=== Example 2: Cache with Default Values ===\n\n";

// Get with default if not found
$settings = $cache->get('app:settings', [
    'theme' => 'light',
    'language' => 'en'
]);

echo "Settings: " . json_encode($settings, JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// Example 3: Remember Pattern
// ===========================

echo "=== Example 3: Remember Pattern ===\n\n";

// Fetch from cache or execute callback
$products = $cache->remember('products:all', function() {
    echo "  [DB] Fetching products from database...\n";
    sleep(2); // Simulate slow query
    
    return [
        ['id' => 1, 'name' => 'Product 1', 'price' => 99.99],
        ['id' => 2, 'name' => 'Product 2', 'price' => 149.99],
        ['id' => 3, 'name' => 'Product 3', 'price' => 199.99],
    ];
}, 600);

echo "✓ First call: Fetched from database\n";
echo "  Products: " . count($products) . "\n\n";

// Second call - will use cache
$products = $cache->remember('products:all', function() {
    echo "  This won't execute!\n";
    return [];
}, 600);

echo "✓ Second call: Retrieved from cache (instant!)\n\n";

// ===========================
// Example 4: Multiple Operations
// ===========================

echo "=== Example 4: Multiple Operations ===\n\n";

// Set multiple values at once
$cache->setMultiple([
    'user:100' => ['name' => 'Alice'],
    'user:101' => ['name' => 'Bob'],
    'user:102' => ['name' => 'Charlie'],
], 3600);

echo "✓ Stored 3 users\n";

// Get multiple values
$users = $cache->getMultiple(['user:100', 'user:101', 'user:102']);
echo "✓ Retrieved " . count($users) . " users\n\n";

// ===========================
// Example 5: Clear Cache
// ===========================

echo "=== Example 5: Clear Cache ===\n\n";

$cache->set('temp:data', 'some value', 60);
echo "✓ Stored temp data\n";

$cache->clear();
echo "✓ Cleared all cache\n\n";

// ===========================
// Example 6: Array Cache (Testing)
// ===========================

echo "=== Example 6: Array Cache (In-Memory) ===\n\n";

$arrayCache = new ArrayCache();

$arrayCache->set('test', 'value', 60);
echo "✓ Stored in memory: " . $arrayCache->get('test') . "\n";
echo "  Perfect for unit tests!\n\n";

// ===========================
// Example 7: Real-World Use Cases
// ===========================

echo "=== Example 7: Real-World Use Cases ===\n\n";

// Use case 1: Cache database query results
class PostRepository
{
    public function __construct(private FileCache $cache) {}
    
    public function findAll(): array
    {
        return $this->cache->remember('posts:all', function() {
            // This would be a database query
            return [
                ['id' => 1, 'title' => 'First Post'],
                ['id' => 2, 'title' => 'Second Post'],
            ];
        }, 600);
    }
    
    public function find(int $id): ?array
    {
        return $this->cache->remember("post:{$id}", function() use ($id) {
            // Database query to find post
            return ['id' => $id, 'title' => 'Post Title'];
        }, 600);
    }
    
    public function clearCache(): void
    {
        $this->cache->clear();
    }
}

echo "PostRepository:\n";
$postRepo = new PostRepository($cache);
$posts = $postRepo->findAll();
echo "  ✓ Fetched posts (cached for 10 minutes)\n\n";

// Use case 2: Cache API responses
class WeatherService
{
    public function __construct(private FileCache $cache) {}
    
    public function getWeather(string $city): array
    {
        $cacheKey = "weather:{$city}";
        
        return $this->cache->remember($cacheKey, function() use ($city) {
            echo "  [API] Calling external weather API for {$city}...\n";
            sleep(1); // Simulate API call
            
            return [
                'city' => $city,
                'temperature' => 22,
                'condition' => 'Sunny',
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }, 1800);
    }
}

echo "WeatherService:\n";
$weather = new WeatherService($cache);
$data = $weather->getWeather('Stockholm');
echo "  ✓ Weather for {$data['city']}: {$data['temperature']}°C, {$data['condition']}\n";
echo "  (Cached for 30 minutes)\n\n";

// Use case 3: Rate limiting
class RateLimiter
{
    public function __construct(private FileCache $cache) {}
    
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $attempts = (int) $this->cache->get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            return false; // Rate limit exceeded
        }
        
        $this->cache->set($key, $attempts + 1, $decaySeconds);
        return true;
    }
    
    public function remaining(string $key, int $maxAttempts): int
    {
        $attempts = (int) $this->cache->get($key, 0);
        return max(0, $maxAttempts - $attempts);
    }
}

echo "RateLimiter:\n";
$limiter = new RateLimiter($cache);
$allowed = $limiter->attempt('api:user:123', 10, 60);
echo "  ✓ API call allowed: " . ($allowed ? 'Yes' : 'No') . "\n";
echo "  ✓ Remaining calls: " . $limiter->remaining('api:user:123', 10) . "\n\n";

// ===========================
// Configuration
// ===========================

echo "=== Configuration (config/cache.php) ===\n\n";

echo "return [\n";
echo "    'default' => env('CACHE_DRIVER', 'file'),\n";
echo "    'stores' => [\n";
echo "        'file' => [\n";
echo "            'driver' => 'file',\n";
echo "            'path' => __DIR__ . '/../storage/cache',\n";
echo "        ],\n";
echo "        'array' => [\n";
echo "            'driver' => 'array',\n";
echo "        ],\n";
echo "    ],\n";
echo "];\n\n";

// ===========================
// Best Practices
// ===========================

echo "=== Best Practices ===\n\n";

echo "1. Use descriptive cache keys\n";
echo "   ✓ 'user:profile:123'\n";
echo "   ✓ 'posts:published:recent'\n";
echo "   ✗ 'data1', 'cache123'\n\n";

echo "2. Set appropriate TTL\n";
echo "   ✓ Frequently changing data: 60-300 seconds\n";
echo "   ✓ Stable data: 3600-86400 seconds\n";
echo "   ✓ Rarely changing: ->forever()\n\n";

echo "3. Use tags for related data\n";
echo "   ✓ Group related cache entries\n";
echo "   ✓ Flush entire groups at once\n\n";

echo "4. Cache expensive operations\n";
echo "   ✓ Database queries\n";
echo "   ✓ API calls\n";
echo "   ✓ Complex calculations\n";
echo "   ✗ Simple array operations\n\n";

echo "5. Implement cache invalidation\n";
echo "   ✓ Clear cache when data changes\n";
echo "   ✓ Use tags for easier invalidation\n";
echo "   ✗ Let stale data persist\n";
