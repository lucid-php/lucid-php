<?php

declare(strict_types=1);

/**
 * Example 16: Standardized API Responses
 * 
 * Demonstrates:
 * - ApiResponse class for consistent API structure
 * - Success, error, and paginated responses
 * - Explicit response creation
 * - Type-safe response handling
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Http\ApiResponse;
use Core\Http\Response;

echo "Standardized API Response Examples:\n";
echo "====================================\n\n";

// ===========================
// 1. Success Response
// ===========================
echo "1. Success Response:\n";

$successResponse = ApiResponse::success(
    data: ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
    message: 'User retrieved successfully'
);

echo json_encode($successResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// 2. Created Response (201)
// ===========================
echo "2. Created Response:\n";

$createdResponse = ApiResponse::created(
    data: ['id' => 42, 'name' => 'New User'],
    message: 'User created successfully'
);

echo "Status Code: {$createdResponse->statusCode}\n";
echo json_encode($createdResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// 3. Error Response
// ===========================
echo "3. Error Response:\n";

$errorResponse = ApiResponse::error(
    message: 'Failed to process request',
    errors: ['field' => 'Invalid value provided'],
    statusCode: 400
);

echo json_encode($errorResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// 4. Validation Error Response
// ===========================
echo "4. Validation Error Response:\n";

$validationResponse = ApiResponse::validationError(
    errors: [
        'email' => ['Email is required', 'Email must be valid'],
        'password' => ['Password must be at least 8 characters']
    ]
);

echo "Status Code: {$validationResponse->statusCode}\n";
echo json_encode($validationResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// 5. Not Found Response
// ===========================
echo "5. Not Found Response:\n";

$notFoundResponse = ApiResponse::notFound('User not found');

echo "Status Code: {$notFoundResponse->statusCode}\n";
echo json_encode($notFoundResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// 6. Unauthorized Response
// ===========================
echo "6. Unauthorized Response:\n";

$unauthorizedResponse = ApiResponse::unauthorized('Authentication required');

echo "Status Code: {$unauthorizedResponse->statusCode}\n";
echo json_encode($unauthorizedResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// 7. Forbidden Response
// ===========================
echo "7. Forbidden Response:\n";

$forbiddenResponse = ApiResponse::forbidden('You do not have permission to access this resource');

echo "Status Code: {$forbiddenResponse->statusCode}\n";
echo json_encode($forbiddenResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// 8. Paginated Response
// ===========================
echo "8. Paginated Response:\n";

$users = [
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane'],
    ['id' => 3, 'name' => 'Bob'],
];

$paginatedResponse = ApiResponse::paginated(
    items: $users,
    total: 50,
    page: 2,
    perPage: 3,
    message: 'Users retrieved successfully'
);

echo json_encode($paginatedResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// 9. No Content Response (204)
// ===========================
echo "9. No Content Response:\n";

$noContentResponse = ApiResponse::noContent();

echo "Status Code: {$noContentResponse->statusCode}\n";
echo "Body: (empty)\n\n";

// ===========================
// 10. Controller Usage Example
// ===========================
echo "10. Controller Usage Example:\n";
echo "-----------------------------\n";

// Simulated controller method using ApiResponse
class UserController
{
    public function index(): ApiResponse
    {
        $users = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        return ApiResponse::success(
            data: $users,
            message: 'Users retrieved successfully'
        );
    }

    public function show(int $id): ApiResponse
    {
        // Simulate database lookup
        if ($id === 999) {
            return ApiResponse::notFound("User with ID {$id} not found");
        }

        return ApiResponse::success(
            data: ['id' => $id, 'name' => 'John Doe', 'email' => 'john@example.com']
        );
    }

    public function store(array $data): ApiResponse
    {
        // Simulate validation
        if (empty($data['email'])) {
            return ApiResponse::validationError(
                errors: ['email' => ['Email is required']]
            );
        }

        // Simulate creation
        $user = ['id' => 42, ...$data];

        return ApiResponse::created(
            data: $user,
            message: 'User created successfully'
        );
    }

    public function delete(int $id): ApiResponse
    {
        // Simulate deletion
        return ApiResponse::noContent();
    }
}

$controller = new UserController();

echo "\nindex():\n";
$response = $controller->index();
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "show(1):\n";
$response = $controller->show(1);
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "show(999) - Not Found:\n";
$response = $controller->show(999);
echo "Status Code: {$response->statusCode}\n";
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "store(['name' => 'Test']) - Validation Error:\n";
$response = $controller->store(['name' => 'Test']);
echo "Status Code: {$response->statusCode}\n";
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "store(['name' => 'Test', 'email' => 'test@example.com']) - Created:\n";
$response = $controller->store(['name' => 'Test', 'email' => 'test@example.com']);
echo "Status Code: {$response->statusCode}\n";
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// Summary
// ===========================
echo "Summary:\n";
echo "--------\n";
echo "✓ Explicit - Every response is clearly created with ApiResponse::method()\n";
echo "✓ Typed - Readonly class with strict types\n";
echo "✓ Consistent - All API responses follow the same structure\n";
echo "✓ Traceable - Command+Click on ApiResponse methods to see implementation\n";
echo "✓ No Magic - No hidden transformations or implicit behavior\n\n";

echo "Standard Response Structure:\n";
echo "{\n";
echo "  \"success\": bool,        // Operation succeeded or failed\n";
echo "  \"data\": mixed,          // Response payload (null if error)\n";
echo "  \"message\": string|null, // Human-readable message\n";
echo "  \"errors\": array|null,   // Validation/error details\n";
echo "  \"meta\": array|null      // Additional metadata (pagination, etc.)\n";
echo "}\n\n";

echo "Common Factory Methods:\n";
echo "  - ApiResponse::success()         200 OK\n";
echo "  - ApiResponse::created()         201 Created\n";
echo "  - ApiResponse::noContent()       204 No Content\n";
echo "  - ApiResponse::error()           400 Bad Request\n";
echo "  - ApiResponse::unauthorized()    401 Unauthorized\n";
echo "  - ApiResponse::forbidden()       403 Forbidden\n";
echo "  - ApiResponse::notFound()        404 Not Found\n";
echo "  - ApiResponse::validationError() 422 Unprocessable Entity\n";
echo "  - ApiResponse::paginated()       200 OK with pagination meta\n";
