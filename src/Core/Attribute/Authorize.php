<?php

declare(strict_types=1);

namespace Core\Attribute;

use Attribute;

/**
 * Declares that a controller method requires authorization.
 *
 * Metadata only — enforcement lives in {@see \Core\Middleware\AuthorizationMiddleware},
 * which delegates the decision to the application's {@see \Core\Security\AuthorizerInterface}.
 * The framework stays unopinionated about users, roles, and policies.
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class Authorize
{
    public function __construct(
        public string $ability
    ) {
    }
}
