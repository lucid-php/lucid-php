<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\MiddlewareInterface;
use Core\Http\MiddlewareStack;
use Core\Http\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;

class TestMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $request = $request->withAttribute('middleware_ran', true);
        return $handler->handle($request);
    }
}

class MiddlewareStackTest extends TestCase
{
    public function testMiddlewareExecutesInOrder(): void
    {
        $middleware = new TestMiddleware();
        $coreAction = function (Request $request): Response {
            $ran = $request->getAttribute('middleware_ran', false);
            return Response::json(['middleware_ran' => $ran]);
        };

        $stack = new MiddlewareStack([$middleware], $coreAction);
        $response = $stack->handle(new Request('GET', '/'));

        $this->assertStringContainsString('"middleware_ran":true', $response->content);
    }

    public function testCoreActionExecutesWithoutMiddleware(): void
    {
        $coreAction = function (Request $request): Response {
            return Response::text('Core executed');
        };

        $stack = new MiddlewareStack([], $coreAction);
        $response = $stack->handle(new Request('GET', '/'));

        $this->assertSame('Core executed', $response->content);
    }
}
