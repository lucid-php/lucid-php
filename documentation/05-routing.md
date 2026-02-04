# Routing & Controllers

Routing is decentralized and declared via Attributes on Controller methods.

## Create a Controller

```php
namespace App\Controllers;

use Core\Attribute\Route;
use Core\Http\Response;

class HomeController 
{
    #[Route('GET', '/')]
    public function index(): Response 
    {
        return Response::text("Hello World");
    }
}
```

## Register the Controller

Controllers MUST be explicitly registered in `public/index.php`. There is no directory scanning.

```php
$app->registerControllers([
    HomeController::class,
    // Add your new controller here
]);
```

## Route Groups

Use `#[RoutePrefix]` to group routes.

```php
#[RoutePrefix('/api/v1')]
class ApiController { ... }
```

## Route Parameters

Extract dynamic parameters from URIs using `{param}` syntax. Parameters are injected into controller methods by name.

```php
// Single parameter - type cast automatically
#[Route('GET', '/users/{id}')]
public function getUser(int $id): Response 
{
    // $id is automatically extracted from URI and cast to int
    return Response::json(['user_id' => $id]);
}

// Multiple parameters
#[Route('GET', '/users/{userId}/posts/{slug}')]
public function getUserPost(int $userId, string $slug): Response 
{
    return Response::json([
        'user_id' => $userId,
        'slug' => $slug
    ]);
}

// Parameters work with other injections
#[Route('PUT', '/users/{id}')]
public function updateUser(int $id, CreateUserDTO $data, Request $request): Response 
{
    // Order doesn't matter - resolved by name and type
    // $id comes from route, $data from request body, $request injected
    return Response::json(['updated' => $id]);
}
```

### Supported Parameter Types

- `int` - Automatically cast to integer
- `string` - Keep as string (default)

### Parameter Matching

- Parameter names in the route must match method parameter names
- Routes with parameters are matched via regex
- Static routes take precedence over parameterized routes

## Query Parameters

Extract query string parameters using the `#[QueryParam]` attribute. Perfect for pagination, filtering, and sorting.

```php
use Core\Attribute\QueryParam;

// Single query parameter with default
#[Route('GET', '/search')]
public function search(#[QueryParam] string $q = ''): Response 
{
    // Access ?q=test from URL
    return Response::json(['query' => $q]);
}

// Multiple query parameters with different types
#[Route('GET', '/users')]
public function listUsers(
    #[QueryParam] int $page = 1,
    #[QueryParam] int $limit = 20,
    #[QueryParam] string $sort = 'id',
    #[QueryParam] bool $active = false
): Response 
{
    // Handles ?page=2&limit=50&sort=name&active=true
    // Types are automatically cast based on type hints
    return Response::json([
        'page' => $page,      // int
        'limit' => $limit,    // int
        'sort' => $sort,      // string
        'active' => $active   // bool
    ]);
}

// Mix with route parameters
#[Route('GET', '/users/{id}/posts')]
public function getUserPosts(
    int $id,                          // From route
    #[QueryParam] int $page = 1,      // From query string
    #[QueryParam] string $status = 'published'  // From query string
): Response 
{
    // Handles /users/123/posts?page=2&status=draft
    return Response::json(['user_id' => $id, 'page' => $page]);
}
```

### Supported Query Parameter Types

- `int` - Cast to integer
- `float` - Cast to float
- `bool` - Parse as boolean (true/false, 1/0, yes/no)
- `string` - Keep as string

### Query Parameter Features

- Default values required (fallback when parameter missing)
- Type casting automatic based on type hint
- Parameter names must match query parameter names
- Must use `#[QueryParam]` attribute to inject (explicit over implicit)

## Pagination

Use the explicit `Paginator` class for pagination calculations. No magic, no global helpers—just math.

```php
use Core\Pagination\Paginator;

#[Route('GET', '/users')]
public function listUsers(
    #[QueryParam] int $page = 1,
    #[QueryParam] int $limit = 20
): Response 
{
    // Get total count from your data source
    $total = $userRepository->count();
    
    // Explicit pagination calculation
    $paginator = new Paginator(total: $total, page: $page, perPage: $limit);
    
    // Use offset and limit in your query
    $users = $userRepository->findAll(
        limit: $paginator->getPerPage(),
        offset: $paginator->getOffset()
    );
    
    // Return data with complete pagination metadata
    return Response::json([
        'data' => $users,
        'pagination' => $paginator->getMetadata()
        // Returns: current_page, per_page, total, last_page, from, to,
        // has_previous_page, has_next_page, previous_page, next_page
    ]);
}
```

### Paginator Methods

- `getOffset()` - Database OFFSET value (e.g., 20 for page 2, perPage 20)
- `getPerPage()` - Database LIMIT value
- `getCurrentPage()` - Current page number
- `getTotal()` - Total number of items
- `getLastPage()` - Last page number
- `getFrom()`, `getTo()` - Item range for "Showing 21-40 of 100"
- `hasPreviousPage()`, `hasNextPage()` - Navigation helpers
- `getPreviousPage()`, `getNextPage()` - Page numbers for navigation
- `getMetadata()` - Complete pagination data as array
- `isEmpty()` - Check if no items

### Paginator Philosophy

- **Not a global helper function** - Explicit instantiation with `new Paginator(...)`
- **No hidden behavior** - Just offset/metadata calculations
- **Returns plain arrays/values** - No magic objects
- Input validation: throws `InvalidArgumentException` on negative total
- Page clamping: auto-adjusts invalid page numbers to valid range
---

## API Responses

Use the `ApiResponse` class for standardized, consistent API responses. All responses follow the same structure.

### Standard Response Structure

Every API response has this structure:

```json
{
  "success": bool,
  "data": mixed,
  "message": string|null,
  "errors": array|null,
  "meta": array|null
}
```

### Success Responses

```php
use Core\Http\ApiResponse;

// Basic success response (200 OK)
#[Route('GET', '/users/{id}')]
public function show(int $id): ApiResponse 
{
    $user = $this->userRepo->find($id);
    
    return ApiResponse::success(
        data: $user,
        message: 'User retrieved successfully'
    );
}

// Created response (201 Created)
#[Route('POST', '/users')]
public function create(CreateUserDTO $data): ApiResponse 
{
    $user = $this->userRepo->create($data);
    
    return ApiResponse::created(
        data: $user,
        message: 'User created successfully'
    );
}

// No content response (204 No Content)
#[Route('DELETE', '/users/{id}')]
public function delete(int $id): ApiResponse 
{
    $this->userRepo->delete($id);
    
    return ApiResponse::noContent();
}
```

### Error Responses

```php
// Not found (404)
if (!$user) {
    return ApiResponse::notFound('User not found');
}

// Validation error (422)
if (!$this->validator->validate($data)) {
    return ApiResponse::validationError(
        errors: $this->validator->errors()
    );
}

// Unauthorized (401)
if (!$this->auth->check()) {
    return ApiResponse::unauthorized('Authentication required');
}

// Forbidden (403)
if (!$this->auth->can('delete', $user)) {
    return ApiResponse::forbidden('You cannot delete this user');
}

// Generic error (400)
return ApiResponse::error(
    message: 'Failed to process request',
    errors: ['field' => 'Invalid value'],
    statusCode: 400
);
```

### Paginated Responses

```php
#[Route('GET', '/users')]
public function index(#[QueryParam] int $page = 1): ApiResponse 
{
    $perPage = 20;
    $users = $this->userRepo->paginate($page, $perPage);
    $total = $this->userRepo->count();
    
    return ApiResponse::paginated(
        items: $users,
        total: $total,
        page: $page,
        perPage: $perPage,
        message: 'Users retrieved successfully'
    );
}

// Response includes pagination metadata:
{
  "success": true,
  "data": [...],
  "message": "Users retrieved successfully",
  "meta": {
    "pagination": {
      "total": 100,
      "page": 2,
      "per_page": 20,
      "total_pages": 5,
      "has_more": true
    }
  }
}
```

### Custom Metadata

```php
return ApiResponse::success(
    data: $users,
    message: 'Users retrieved',
    meta: [
        'filters_applied' => ['status' => 'active'],
        'request_id' => uniqid(),
        'execution_time' => 0.123
    ]
);
```

### Router Integration

The Router automatically converts `ApiResponse` to HTTP `Response`:

```php
// In your controller, return ApiResponse
public function show(int $id): ApiResponse 
{
    return ApiResponse::success($user);
}

// Router automatically calls ->toResponse() and sends HTTP response
// No manual conversion needed - it's explicit and automatic
```

### Available Factory Methods

| Method | Status Code | Use Case |
|--------|-------------|----------|
| `success()` | 200 | Successful GET/PUT operations |
| `created()` | 201 | Resource created successfully |
| `noContent()` | 204 | Successful DELETE, no body needed |
| `error()` | 400 | Generic client error |
| `unauthorized()` | 401 | Authentication required |
| `forbidden()` | 403 | Authenticated but no permission |
| `notFound()` | 404 | Resource doesn't exist |
| `validationError()` | 422 | Request validation failed |
| `paginated()` | 200 | List with pagination metadata |

### Philosophy Compliance

- **Explicit** - Each response explicitly calls a factory method
- **Typed** - Readonly class with strict types throughout
- **Traceable** - Command+Click on factory methods works perfectly
- **No Magic** - Structure is explicit, no hidden transformations
- **Consistent** - All APIs use the same response format

---