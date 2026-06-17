<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Attribute\Authorize;
use Core\Http\ForbiddenException;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;
use Core\Security\AuthorizerInterface;
use ReflectionClass;

/**
 * Enforces #[Authorize] on routed controller methods.
 *
 * Philosophy: explicit and traceable. Only routes that carry the #[Authorize]
 * attribute are checked (no implicit protection); the decision is delegated to
 * the app-provided AuthorizerInterface. Denials raise the existing
 * ForbiddenException (handled by ExceptionMiddleware).
 */
class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthorizerInterface $authorizer
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authorize = $this->getAuthorizeAttribute($request);

        if ($authorize !== null && !$this->authorizer->authorize($authorize->ability, $request)) {
            throw new ForbiddenException("Not authorized for ability: {$authorize->ability}");
        }

        return $handler->handle($request);
    }

    /**
     * Read the #[Authorize] attribute from the routed controller method, if any.
     */
    private function getAuthorizeAttribute(Request $request): ?Authorize
    {
        $controller = $request->getAttribute('_controller');
        $method = $request->getAttribute('_method');

        if ($controller === null || $method === null) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($controller);
            $methodReflection = $reflection->getMethod($method);
            $attributes = $methodReflection->getAttributes(Authorize::class);

            if ($attributes === []) {
                return null;
            }

            return $attributes[0]->newInstance();
        } catch (\ReflectionException) {
            return null;
        }
    }
}
