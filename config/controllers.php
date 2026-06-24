<?php

declare(strict_types=1);

use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use Core\GraphQL\GraphQLController;

/**
 * HTTP Controllers
 *
 * Explicitly register every controller whose #[Route] methods should be
 * dispatchable. No auto-discovery — this list is the single source of truth,
 * read by both public/index.php (to serve requests) and the route:list /
 * route:match console commands (to introspect them).
 */
return [
    'list' => [
        HomeController::class,
        ApiController::class,
        AuthController::class,
        GraphQLController::class,
    ],
];
