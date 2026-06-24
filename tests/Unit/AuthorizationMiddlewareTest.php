<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\Authorize;
use Core\Http\ForbiddenException;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;
use Core\Middleware\AuthorizationMiddleware;
use Core\Security\AuthorizerInterface;
use PHPUnit\Framework\TestCase;

class AuthorizationMiddlewareTest extends TestCase
{
    public function testPassesThroughWhenNoAuthorizeAttribute(): void
    {
        $authorizer = $this->createMock(AuthorizerInterface::class);
        $authorizer->expects($this->never())->method('authorize');

        $middleware = new AuthorizationMiddleware($authorizer);
        $request = $this->routedRequest('publicAction');

        $response = $middleware->process($request, $this->handler());

        $this->assertSame(200, $response->status);
    }

    public function testAllowsWhenAuthorizerGrants(): void
    {
        $authorizer = $this->createMock(AuthorizerInterface::class);
        $authorizer->expects($this->once())
            ->method('authorize')
            ->with('users.delete', $this->isInstanceOf(Request::class))
            ->willReturn(true);

        $middleware = new AuthorizationMiddleware($authorizer);
        $response = $middleware->process($this->routedRequest('protectedAction'), $this->handler());

        $this->assertSame(200, $response->status);
    }

    public function testDeniesWithForbiddenWhenAuthorizerRejects(): void
    {
        $authorizer = $this->createStub(AuthorizerInterface::class);
        $authorizer->method('authorize')->willReturn(false);

        $middleware = new AuthorizationMiddleware($authorizer);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Not authorized for ability: users.delete');

        $middleware->process($this->routedRequest('protectedAction'), $this->handler());
    }

    private function routedRequest(string $method): Request
    {
        return (new Request('DELETE', '/users/1'))
            ->withAttribute('_controller', AuthzTestController::class)
            ->withAttribute('_method', $method);
    }

    private function handler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                return new Response('OK', 200);
            }
        };
    }
}

class AuthzTestController
{
    public function publicAction(): Response
    {
        return new Response('public', 200);
    }

    #[Authorize('users.delete')]
    public function protectedAction(): Response
    {
        return new Response('deleted', 200);
    }
}
