<?php

declare(strict_types=1);

/**
 * Example 13: HTTP Client
 * 
 * Demonstrates:
 * - Making HTTP requests (GET, POST, PUT, DELETE)
 * - Request/Response objects
 * - JSON handling
 * - Authentication (Bearer, Basic)
 * - MockHttpClient for testing
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Http\Client\HttpRequest;
use Core\Http\Client\HttpResponse;
use Core\Http\Client\CurlHttpClient;
use Core\Http\Client\MockHttpClient;

echo "HTTP Client Examples:\n";
echo "=====================\n\n";

// ===========================
// Example 1: Simple GET Request
// ===========================

echo "=== Example 1: Simple GET Request ===\n\n";

$request = HttpRequest::get('https://jsonplaceholder.typicode.com/posts/1');

echo "Request:\n";
echo "  Method: {$request->method}\n";
echo "  URL: {$request->url}\n\n";

echo "To send:\n";
echo "  \$client = new CurlHttpClient();\n";
echo "  \$response = \$client->send(\$request);\n";
echo "  \$data = \$response->json();\n\n";

// ===========================
// Example 2: POST Request with JSON
// ===========================

echo "=== Example 2: POST Request with JSON ===\n\n";

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
];

$request = HttpRequest::post(
    'https://api.example.com/users',
    json_encode($data)
)->asJson();

echo "Request:\n";
echo "  Method: {$request->method}\n";
echo "  URL: {$request->url}\n";
echo "  Content-Type: " . ($request->headers['Content-Type'] ?? 'none') . "\n";
echo "  Body: " . $request->body . "\n\n";

// ===========================
// Example 3: Authentication
// ===========================

echo "=== Example 3: Authentication ===\n\n";

// Bearer Token
$request = HttpRequest::get('https://api.example.com/protected')
    ->withBearerToken('your-secret-token-here');

echo "Bearer Token Authentication:\n";
echo "  Authorization: Bearer your-secret-token-here\n\n";

// Basic Authentication
$request = HttpRequest::get('https://api.example.com/admin')
    ->withBasicAuth('username', 'password');

echo "Basic Authentication:\n";
echo "  Authorization: Basic " . base64_encode('username:password') . "\n\n";

// ===========================
// Example 4: Custom Headers
// ===========================

echo "=== Example 4: Custom Headers ===\n\n";

$request = HttpRequest::get('https://api.example.com/data')
    ->withHeader('Accept', 'application/json')
    ->withHeader('User-Agent', 'MyApp/1.0')
    ->withHeader('X-API-Version', 'v2');

echo "Request headers:\n";
foreach ($request->headers as $name => $value) {
    echo "  $name: $value\n";
}
echo "\n";

// ===========================
// Example 5: Query Parameters
// ===========================

echo "=== Example 5: Query Parameters ===\n\n";

$params = http_build_query([
    'page' => 2,
    'limit' => 10,
    'sort' => 'created_at',
    'order' => 'desc'
]);

$request = HttpRequest::get('https://api.example.com/users?' . $params);

echo "Generated URL: {$request->url}\n\n";

// ===========================
// Example 6: MockHttpClient for Testing
// ===========================

echo "=== Example 6: MockHttpClient for Testing ===\n\n";

$mock = new MockHttpClient();

// Queue responses
$mock->queueResponse(new HttpResponse(
    200,
    json_encode(['id' => 1, 'name' => 'Test User'])
));

$mock->queueResponse(new HttpResponse(
    404,
    json_encode(['error' => 'Not found'])
));

// Make requests
$request1 = HttpRequest::get('https://api.example.com/users/1');
$response1 = $mock->send($request1);

echo "First request:\n";
echo "  Status: {$response1->statusCode}\n";
echo "  Body: {$response1->body}\n";
echo "  Data: " . json_encode($response1->json()) . "\n\n";

$request2 = HttpRequest::get('https://api.example.com/users/999');
$response2 = $mock->send($request2);

echo "Second request:\n";
echo "  Status: {$response2->statusCode}\n";
echo "  Body: {$response2->body}\n\n";

// Inspect requests
echo "Total requests made: " . $mock->count() . "\n";
$lastRequest = $mock->getLastRequest();
if ($lastRequest) {
    echo "Last request URL: {$lastRequest->url}\n";
}
echo "\n";

// ===========================
// Example 7: Response Handling
// ===========================

echo "=== Example 7: Response Handling ===\n\n";

$mock = new MockHttpClient();
$mock->queueResponse(new HttpResponse(
    200,
    json_encode([
        'users' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ],
        'total' => 2
    ]),
    ['Content-Type' => 'application/json']
));

$request = HttpRequest::get('https://api.example.com/users');
$response = $mock->send($request);

echo "Response:\n";
echo "  Status: {$response->statusCode}\n";
echo "  Is success: " . ($response->isSuccessful() ? 'yes' : 'no') . "\n";
echo "  Content-Type: " . ($response->headers['Content-Type'] ?? 'none') . "\n\n";

$data = $response->json();
echo "Parsed JSON:\n";
echo "  Total users: {$data['total']}\n";
foreach ($data['users'] as $user) {
    echo "  - {$user['name']} (ID: {$user['id']})\n";
}
echo "\n";

// ===========================
// Example 8: API Client Class
// ===========================

echo "=== Example 8: API Client Class ===\n\n";

use Core\Http\Client\HttpClientInterface;

class ApiClient
{
    private string $baseUrl;
    
    public function __construct(
        private HttpClientInterface $client,
        string $baseUrl,
        private ?string $apiToken = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function getUser(int $id): array
    {
        $request = HttpRequest::get("{$this->baseUrl}/users/{$id}");
        
        if ($this->apiToken) {
            $request = $request->withBearerToken($this->apiToken);
        }
        
        $response = $this->client->send($request);
        
        if (!$response->isSuccessful()) {
            throw new \Exception("API error: {$response->statusCode}");
        }
        
        return $response->json();
    }
    
    public function createUser(array $data): array
    {
        $request = HttpRequest::post("{$this->baseUrl}/users", json_encode($data))
            ->asJson();
        
        if ($this->apiToken) {
            $request = $request->withBearerToken($this->apiToken);
        }
        
        $response = $this->client->send($request);
        
        return $response->json();
    }
}

// Use with mock client for testing
$mock = new MockHttpClient();
$mock->queueResponse(new HttpResponse(
    200,
    json_encode(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'])
));

$api = new ApiClient($mock, 'https://api.example.com', 'test-token');
$user = $api->getUser(1);

echo "API Client usage:\n";
echo "  Fetched user: {$user['name']} ({$user['email']})\n";
echo "  Request was authenticated: " . ($mock->getLastRequest()->headers['Authorization'] ?? 'no') . "\n\n";

// ===========================
// Example 9: Error Handling
// ===========================

echo "=== Example 9: Error Handling ===\n\n";

$mock = new MockHttpClient();
$mock->queueResponse(new HttpResponse(
    500,
    json_encode(['error' => 'Internal server error'])
));

try {
    $request = HttpRequest::get('https://api.example.com/data');
    $response = $mock->send($request);
    
    if (!$response->isSuccessful()) {
        echo "Error response:\n";
        echo "  Status: {$response->statusCode}\n";
        echo "  Message: " . ($response->json()['error'] ?? 'Unknown error') . "\n\n";
    }
} catch (\Exception $e) {
    echo "Exception: {$e->getMessage()}\n\n";
}

// ===========================
// Example 10: Testing Assertions
// ===========================

echo "=== Example 10: Testing Assertions ===\n\n";

$mock = new MockHttpClient();
$mock->queueResponse(new HttpResponse(200, '{"status":"ok"}'));

// Make request
$data = ['event' => 'user.created', 'user_id' => 123];
$request = HttpRequest::post('https://api.example.com/webhook', json_encode($data))
    ->asJson();

$response = $mock->send($request);

// Assert request was made
$wasSent = $mock->assertSent(function ($req) {
    return $req->method === 'POST'
        && str_contains($req->url, '/webhook')
        && json_decode($req->body, true)['event'] === 'user.created';
});

echo "Assertion result: " . ($wasSent ? 'PASSED' : 'FAILED') . "\n";
echo "  Method was POST: " . ($mock->getLastRequest()->method === 'POST' ? '✓' : '✗') . "\n";
echo "  URL contains /webhook: " . (str_contains($mock->getLastRequest()->url, '/webhook') ? '✓' : '✗') . "\n";
echo "  Body contains event: " . (str_contains($mock->getLastRequest()->body, 'user.created') ? '✓' : '✗') . "\n\n";

// ===========================
// Configuration
// ===========================

echo "=== Configuration (config/http_client.php) ===\n\n";

echo "return [\n";
echo "    'timeout' => 30,              // Request timeout in seconds\n";
echo "    'verify_ssl' => true,         // Verify SSL certificates\n";
echo "    'user_agent' => 'MyApp/1.0',  // User-Agent header\n";
echo "];\n\n";

// ===========================
// Best Practices
// ===========================

echo "=== Best Practices ===\n\n";

echo "1. Use typed Request/Response objects\n";
echo "   ✓ HttpRequest::get(), ::post(), ::put(), ::delete()\n";
echo "   ✓ Immutable request building\n\n";

echo "2. Handle errors explicitly\n";
echo "   ✓ Check \$response->isSuccess()\n";
echo "   ✓ Handle different status codes appropriately\n\n";

echo "3. Use MockHttpClient for tests\n";
echo "   ✓ No actual HTTP calls in tests\n";
echo "   ✓ Fast and reliable\n";
echo "   ✓ Inspect what was sent\n\n";

echo "4. Inject HttpClientInterface\n";
echo "   ✓ Allows swapping real/mock clients\n";
echo "   ✓ Better testability\n\n";

echo "5. Set appropriate timeouts\n";
echo "   ✓ Default 30 seconds\n";
echo "   ✓ Adjust based on expected response time\n\n";
