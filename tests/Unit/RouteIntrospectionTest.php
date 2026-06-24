<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\Route;
use Core\Attribute\RoutePrefix;
use Core\Container;
use Core\Http\Response;
use Core\Router;
use PHPUnit\Framework\TestCase;

class RouteIntrospectionTest extends TestCase
{
    public function testGetRoutesReturnsAllRegisteredRoutes(): void
    {
        $router = new Router(new Container());
        $router->registerControllers([IntrospectionController::class]);

        $routes = $router->getRoutes();
        $this->assertCount(2, $routes);

        $paths = array_map(fn (array $r): string => $r['method'] . ' ' . $r['path'], $routes);
        sort($paths);
        $this->assertSame(['GET /things', 'GET /things/{id}'], $paths);

        $show = array_values(array_filter($routes, fn (array $r): bool => $r['path'] === '/things/{id}'))[0];
        $this->assertSame(IntrospectionController::class, $show['controller']);
        $this->assertSame('show', $show['action']);
    }

    public function testMatchResolvesParamsAndReturnsNullForNoMatch(): void
    {
        $router = new Router(new Container());
        $router->registerControllers([IntrospectionController::class]);

        $matched = $router->match('GET', '/things/42');
        $this->assertNotNull($matched);
        $this->assertSame('show', $matched['route']['method']);
        $this->assertSame(['id' => '42'], $matched['params']);

        $this->assertNull($router->match('GET', '/nope'));
        $this->assertNull($router->match('POST', '/things'), 'method mismatch returns null');
    }
}

#[RoutePrefix('/things')]
class IntrospectionController
{
    #[Route('GET', '')]
    public function index(): Response
    {
        return Response::json([]);
    }

    #[Route('GET', '/{id}')]
    public function show(): Response
    {
        return Response::json([]);
    }
}
