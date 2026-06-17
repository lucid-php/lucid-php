<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\Cache;
use Core\Attribute\Route;
use Core\Cache\ArrayCache;
use Core\Container;
use Core\Http\Request;
use Core\Http\Response;
use Core\Router;
use PHPUnit\Framework\TestCase;

class RouterCacheTest extends TestCase
{
    public function testCachedGetRunsControllerOnlyOnce(): void
    {
        CacheCounterController::$calls = 0;

        $router = $this->router();
        $router->registerControllers([CacheCounterController::class]);

        $first = $router->dispatch(new Request('GET', '/cached'));
        $second = $router->dispatch(new Request('GET', '/cached'));

        $this->assertSame(200, $first->status);
        $this->assertSame($first->content, $second->content);
        $this->assertSame(1, CacheCounterController::$calls, 'second GET served from cache');
    }

    public function testAuthenticatedRequestIsNotCached(): void
    {
        CacheCounterController::$calls = 0;

        $router = $this->router();
        $router->registerControllers([CacheCounterController::class]);

        $server = ['HTTP_AUTHORIZATION' => 'Bearer abc'];
        $router->dispatch(new Request('GET', '/cached', server: $server));
        $router->dispatch(new Request('GET', '/cached', server: $server));

        $this->assertSame(2, CacheCounterController::$calls, 'authenticated responses are never cached');
    }

    public function testUncachedRouteAlwaysRunsController(): void
    {
        CacheCounterController::$calls = 0;

        $router = $this->router();
        $router->registerControllers([CacheCounterController::class]);

        $router->dispatch(new Request('GET', '/uncached'));
        $router->dispatch(new Request('GET', '/uncached'));

        $this->assertSame(2, CacheCounterController::$calls);
    }

    private function router(): Router
    {
        return new Router(new Container(), new ArrayCache());
    }
}

class CacheCounterController
{
    public static int $calls = 0;

    #[Route('GET', '/cached')]
    #[Cache(ttl: 60)]
    public function cached(): Response
    {
        self::$calls++;
        return Response::json(['n' => self::$calls]);
    }

    #[Route('GET', '/uncached')]
    public function uncached(): Response
    {
        self::$calls++;
        return Response::json(['n' => self::$calls]);
    }
}
