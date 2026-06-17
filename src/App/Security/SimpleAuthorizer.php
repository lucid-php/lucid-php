<?php

declare(strict_types=1);

namespace App\Security;

use Core\Http\Request;
use Core\Security\AuthorizerInterface;

/**
 * Reference authorizer for the sample application.
 *
 * Reads the authenticated principal placed on the request by AuthMiddleware
 * ('user' attribute) and maps abilities to a simple rule. A real app would
 * delegate to roles/permissions/policies here — the framework stays out of it.
 */
class SimpleAuthorizer implements AuthorizerInterface
{
    public function authorize(string $ability, Request $request): bool
    {
        $user = $request->getAttribute('user');

        return match ($ability) {
            'users.delete' => isset($user->is_admin) && $user->is_admin,
            default => false,
        };
    }
}
