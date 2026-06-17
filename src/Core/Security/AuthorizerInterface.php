<?php

declare(strict_types=1);

namespace Core\Security;

use Core\Http\Request;

/**
 * Contract the application implements to make authorization decisions.
 *
 * The framework calls this when a route carries #[Authorize]. How the principal
 * is identified (e.g. the 'user' request attribute set by an auth middleware)
 * and how abilities map to roles/permissions is entirely the app's concern —
 * Core stays unopinionated.
 */
interface AuthorizerInterface
{
    /**
     * Return true if the current request is allowed to perform the ability.
     */
    public function authorize(string $ability, Request $request): bool;
}
