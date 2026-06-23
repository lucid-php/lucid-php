<?php

declare(strict_types=1);

/**
 * Authorization (Role -> Permissions) Configuration
 *
 * Maps each role to the set of abilities it may perform. App\Security\
 * SimpleAuthorizer reads this when a route carries #[Authorize('<ability>')].
 *
 * Philosophy: Explicit over convenient.
 * - The principal's role comes from the authenticated User entity (users.role).
 * - An ability is granted only if it is listed for the user's role (or the role
 *   lists the '*' wildcard). Unknown roles and unlisted abilities are denied.
 * - No implicit super-user: 'admin' is powerful only because it is listed here.
 */
return [
    'roles' => [
        'admin' => [
            'users.delete',
            'admin.broadcast',
        ],

        // Default role for newly created users — read-only by default.
        'user' => [],
    ],
];
