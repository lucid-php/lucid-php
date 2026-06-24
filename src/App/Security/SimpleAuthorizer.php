<?php

declare(strict_types=1);

namespace App\Security;

use Core\Config\Config;
use Core\Http\Request;
use Core\Security\AuthorizerInterface;

/**
 * Role-based authorizer for the sample application.
 *
 * Reads the authenticated principal placed on the request by AuthMiddleware
 * (the 'user' attribute) and grants an ability only if it is listed for the
 * user's role in config/authorization.php (or that role lists the '*' wildcard).
 *
 * Fails closed: no authenticated user, an unknown role, or an unlisted ability
 * all result in denial. There is no implicit super-user — 'admin' is privileged
 * only because the config grants it those abilities.
 */
class SimpleAuthorizer implements AuthorizerInterface
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function authorize(string $ability, Request $request): bool
    {
        $user = $request->getAttribute('user');

        if ($user === null) {
            return false;
        }

        $role = $user->role ?? 'user';

        $roles = $this->config->all('authorization')['roles'] ?? [];
        $permissions = $roles[$role] ?? [];

        return in_array('*', $permissions, true)
            || in_array($ability, $permissions, true);
    }
}
