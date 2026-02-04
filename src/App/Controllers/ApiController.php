<?php

declare(strict_types=1);

/**
 * API Controller - Comprehensive Attribute Showcase
 * 
 * This controller demonstrates all available framework attributes:
 * 
 * CLASS LEVEL:
 * - #[RoutePrefix('/api')] - Groups all routes under /api
 * - #[Middleware(LoggerMiddleware::class)] - Applies to ALL methods in this controller
 * 
 * METHOD LEVEL COMBINATIONS:
 * 1. #[Route] - Defines HTTP method and path
 * 2. #[Middleware] - Can stack multiple (Auth, CORS, etc.)
 * 3. #[RateLimit] - Explicit rate limiting per endpoint
 * 
 * RATE LIMIT STRATEGIES SHOWN:
 * - status(): 1000/min (generous for health checks)
 * - me(), users(): 100/min (moderate for standard API)
 * - createUser(): 10/min (strict for write operations)
 * - deleteUser(): 5/min (very strict for destructive operations)
 * - adminBroadcast(): 1/min (extreme for admin actions)
 * - unlimitedEndpoint(): NO LIMIT (opt-in, not default)
 * 
 * MIDDLEWARE PATTERNS SHOWN:
 * - Single middleware: #[Middleware(AuthMiddleware::class)]
 * - Multiple stacked: #[Middleware(Auth)] + #[Middleware(CORS)]
 * - Class-level + method-level inheritance
 * 
 * HTTP METHODS SHOWN:
 * - GET (read operations)
 * - POST (create operations)
 * - PUT (full update)
 * - PATCH (partial update)
 * - DELETE (destructive operations)
 * 
 * EXCEPTION HANDLING SHOWN:
 * - NotFoundException (404)
 * - ForbiddenException (403)
 * - UnauthorizedException (401 - in middleware)
 * 
 * PHILOSOPHY:
 * - All behavior visible on the method itself
 * - No hidden defaults
 * - Explicit over convenient
 * - Can Command+Click to any attribute definition
 */

namespace App\Controllers;

use App\DTO\CreateUserDTO;
use App\Event\UserCreated;
use App\Event\UserDeleted;
use App\Repository\UserRepository;
use Core\Attribute\RateLimit;
use Core\Attribute\Route;
use Core\Attribute\RoutePrefix;
use Core\Attribute\Middleware;
use Core\Attribute\QueryParam;
use Core\Event\EventDispatcher;
use Core\Pagination\Paginator;
use App\Middleware\LoggerMiddleware;
use App\Middleware\AuthMiddleware;
use Core\Http\NotFoundException;
use Core\Http\ForbiddenException;
use Core\Middleware\CorsMiddleware;
use Core\Http\Request;
use Core\Http\Response;

#[RoutePrefix('/api')]
#[Middleware(LoggerMiddleware::class)]
class ApiController
{
    public function __construct(
        private final UserRepository $userRepository,
        private final EventDispatcher $events
    ) {}

    // Generous rate limit for health checks
    #[Route('GET', '/status')]
    #[RateLimit(requests: 1000, window: 60)]
    public function status(): array
    {
        return ['status' => 'active', 'service' => 'api'];
    }

    /**
     * GET /api/users/view - HTML view of all users
     * Example of rendering HTML templates with Response::view()
     */
    #[Route('GET', '/users/view')]
    #[RateLimit(requests: 100, window: 60)]
    public function usersView(): Response
    {
        $users = $this->userRepository->findAll();
        
        // Convert User entities to arrays for view
        $usersData = array_map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at
        ], $users);
        
        // Explicit view rendering - no magic
        return Response::view('users/index', [
            'title' => 'Users',
            'subtitle' => 'All registered users in the system',
            'users' => $usersData
        ]);
    }

    // Authenticated route with moderate rate limit
    #[Route('GET', '/me')]
    #[Middleware(AuthMiddleware::class)]
    #[RateLimit(requests: 100, window: 60)]
    public function me(Request $request): array
    {
        $user = $request->getAttribute('user');
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ];
    }

    // Strict rate limit for user creation
    #[Route('POST', '/users')]
    #[RateLimit(requests: 10, window: 60)]
    public function createUser(CreateUserDTO $data): Response
    {
        $user = $this->userRepository->create(
            $data->name,
            $data->email,
            $data->password
        );
// Explicit event dispatch - no magic
        $this->events->dispatch(new UserCreated(
            userId: $user->id,
            name: $user->name,
            email: $user->email
        ));

        
        return Response::json([
            'message' => 'User created',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ], 201);
    }

    /**
     * GET /api/users?page=1&limit=10&sort=name
     * 
     * Demonstrates pagination with query parameters:
     * - Explicit Paginator class (no magic)
     * - Query parameters for page/limit/sort
     * - Manual calculation of offset
     * - Complete pagination metadata in response
     * 
     * Philosophy compliance:
     * - Paginator is explicitly instantiated (new Paginator(...))
     * - No global helpers or facades
     * - All behavior visible in code
     */
    #[Route('GET', '/users')]
    #[RateLimit(requests: 100, window: 60)]
    public function users(
        #[QueryParam] int $page = 1,
        #[QueryParam] int $limit = 10,
        #[QueryParam] string $sort = 'id'
    ): array
    {
        $allUsers = $this->userRepository->findAll();
        $total = count($allUsers);
        
        // Explicit pagination - no magic
        $paginator = new Paginator(total: $total, page: $page, perPage: $limit);
        
        // Apply pagination manually
        $paginatedUsers = array_slice(
            $allUsers,
            $paginator->getOffset(),
            $paginator->getPerPage()
        );
        
        // In real implementation, pass offset/limit to repository:
        // $users = $this->userRepository->findAll($paginator->getPerPage(), $paginator->getOffset());
        
        return [
            'data' => array_map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'joined' => $user->created_at
            ], $paginatedUsers),
            'pagination' => $paginator->getMetadata()
        ];
    }

    /**
     * GET /api/users/{id}
     * 
     * Route parameter injection:
     * - {id} in route path is extracted and injected as int $id parameter
     * - Type casting happens automatically (string to int)
     * - Auth required (middleware)
     * - Moderate rate limit
     */
    #[Route('GET', '/users/{id}')]
    #[Middleware(AuthMiddleware::class)]
    #[RateLimit(requests: 100, window: 60)]
    public function getUser(int $id): Response
    {
        $users = $this->userRepository->findAll();
        
        // PHP 8.5: array_first() with array_filter for cleaner lookup
        $user = array_first(array_filter($users, fn($u) => $u->id === $id));
        
        if (!$user) {
            throw new NotFoundException("User with ID $id not found");
        }

        return Response::json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at
        ]);
    }

    /**
     * DELETE /api/users/{id}
     * 
     * Demonstrates:
     * - DELETE method with route parameter
     * - {id} automatically injected and type-cast to int
     * - Multiple middleware (Auth + CORS)
     * - Strict rate limit (destructive operation)
     * - Typed exception throwing
     */
    #[Route('DELETE', '/users/{id}')]
    #[Middleware(AuthMiddleware::class)]
    #[Middleware(CorsMiddleware::class)]
    #[RateLimit(requests: 5, window: 60)]
    public function deleteUser(int $id, Request $request): Response
    {
        $currentUser = $request->getAttribute('user');
        
        // Example: Only admins can delete users
        if (!isset($currentUser->is_admin) || !$currentUser->is_admin) {
            throw new ForbiddenException('Admin access required');
        }

        // Find user before deletion for event
        $users = $this->userRepository->findAll();
        $user = array_first(array_filter($users, fn($u) => $u->id === $id));
        
        if (!$user) {
            throw new NotFoundException("User with ID $id not found");
        }

        // In real app, call $this->userRepository->delete($id);
        
        // Explicit event dispatch
        $this->events->dispatch(new UserDeleted(
            userId: $user->id,
            email: $user->email
        ));

        return Response::json([
            'message' => "User $id deleted successfully"
        ]);
    }

    /**
     * PUT /api/users/{id}
     * 
     * Demonstrates:
     * - PUT method for full resource update
     * - Route parameter + DTO + Request all injected together
     * - Parameter order doesn't matter (resolved by name/type)
     * - Auth required
     * - Moderate rate limit
     */
    #[Route('PUT', '/users/{id}')]
    #[Middleware(AuthMiddleware::class)]
    #[RateLimit(requests: 20, window: 60)]
    public function updateUser(int $id, CreateUserDTO $data, Request $request): Response
    {
        $currentUser = $request->getAttribute('user');
        
        // Update logic would go here
        return Response::json([
            'message' => "User $id updated successfully",
            'user' => [
                'id' => $id,
                'name' => $data->name,
                'email' => $data->email
            ]
        ]);
    }

    /**
     * PATCH /api/users/{id}/status/{status}
     * 
     * Demonstrates:
     * - PATCH method for partial update
     * - Multiple route parameters in single route
     * - Both int and string parameters
     * - Auth required
     * - Moderate rate limit
     */
    #[Route('PATCH', '/users/{id}/status/{status}')]
    #[Middleware(AuthMiddleware::class)]
    #[RateLimit(requests: 30, window: 60)]
    public function patchUserStatus(int $id, string $status, Request $request): Response
    {
        return Response::json([
            'message' => "User $id status updated to $status",
            'user_id' => $id,
            'new_status' => $status
        ]);
    }

    /**
     * GET /api/search?q=john&status=active&verified=true
     * 
     * Demonstrates query parameters for filtering:
     * - String query parameter (search term)
     * - String filter (status)
     * - Boolean filter (verified)
     * - All parameters optional with sensible defaults
     * - Real-world search/filter pattern
     */
    #[Route('GET', '/search')]
    #[RateLimit(requests: 50, window: 60)]
    public function search(
        #[QueryParam] string $q = '',
        #[QueryParam] string $status = 'all',
        #[QueryParam] bool $verified = false
    ): Response
    {
        $users = $this->userRepository->findAll();
        
        // Demonstrate filters are applied (in real app, this would be SQL WHERE clauses)
        $results = array_filter($users, function($user) use ($q, $status, $verified) {
            if ($q !== '' && !str_contains(strtolower($user->name), strtolower($q))) {
                return false;
            }
            // In real app, check status and verified fields
            return true;
        });
        
        return Response::json([
            'results' => array_values(array_map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email
            ], $results)),
            'filters' => [
                'query' => $q,
                'status' => $status,
                'verified' => $verified
            ],
            'count' => count($results)
        ]);
    }

    /**
     * GET /api/public/stats
     * 
     * Demonstrates:
     * - Public endpoint (no auth middleware)
     * - CORS enabled for cross-origin access
     * - Very generous rate limit
     * - No authentication required
     */
    #[Route('GET', '/public/stats')]
    #[Middleware(CorsMiddleware::class)]
    #[RateLimit(requests: 500, window: 60)]
    public function publicStats(): array
    {
        return [
            'total_users' => count($this->userRepository->findAll()),
            'api_version' => '1.0',
            'public' => true
        ];
    }

    /**
     * POST /api/admin/broadcast
     * 
     * Demonstrates:
     * - Admin-only route
     * - Very strict rate limit (1 per minute)
     * - Multiple middleware stacked
     * - POST for action endpoint
     */
    #[Route('POST', '/admin/broadcast')]
    #[Middleware(AuthMiddleware::class)]
    #[Middleware(CorsMiddleware::class)]
    #[RateLimit(requests: 1, window: 60)]
    public function adminBroadcast(Request $request): Response
    {
        $currentUser = $request->getAttribute('user');
        
        if (!isset($currentUser->is_admin) || !$currentUser->is_admin) {
            throw new ForbiddenException('Admin access required');
        }

        // Broadcast logic would go here
        return Response::json([
            'message' => 'Broadcast sent successfully',
            'timestamp' => time()
        ]);
    }

    /**
     * GET /api/unlimited
     * 
     * Demonstrates:
     * - Route WITHOUT rate limiting (intentionally)
     * - Shows that rate limits are opt-in, not default
     * - Auth still required
     */
    #[Route('GET', '/unlimited')]
    #[Middleware(AuthMiddleware::class)]
    public function unlimitedEndpoint(Request $request): array
    {
        return [
            'message' => 'This endpoint has no rate limit',
            'user_id' => $request->getAttribute('user')->id
        ];
    }
}

