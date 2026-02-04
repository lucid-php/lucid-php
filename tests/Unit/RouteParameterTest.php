<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Container;
use Core\Router;
use Core\Attribute\Route;
use Core\Http\Request;
use Core\Http\Response;
use PHPUnit\Framework\TestCase;

class RouteParameterTest extends TestCase
{
    private Container $container;
    private Router $router;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->router = new Router($this->container);
    }

    public function test_it_extracts_single_parameter(): void
    {
        $this->router->registerControllers([RouteParamTestController::class]);

        $request = new Request('GET', '/users/123', [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame('123', $data['id']);
        $this->assertSame('string', $data['type']);
    }

    public function test_it_casts_parameter_to_int(): void
    {
        $this->router->registerControllers([RouteParamTestController::class]);

        $request = new Request('GET', '/posts/456', [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame(456, $data['id']);
        $this->assertSame('integer', $data['type']);
    }

    public function test_it_extracts_multiple_parameters(): void
    {
        $this->router->registerControllers([RouteParamTestController::class]);

        $request = new Request('GET', '/users/789/posts/abc-slug', [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame(789, $data['userId']);
        $this->assertSame('abc-slug', $data['slug']);
    }

    public function test_it_handles_route_without_parameters(): void
    {
        $this->router->registerControllers([RouteParamTestController::class]);

        $request = new Request('GET', '/static', [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame('no-params', $data['result']);
    }

    public function test_it_returns_404_for_non_matching_route(): void
    {
        $this->router->registerControllers([RouteParamTestController::class]);

        $request = new Request('GET', '/nonexistent', [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(404, $response->status);
    }

    public function test_parameters_can_coexist_with_request_injection(): void
    {
        $this->router->registerControllers([RouteParamTestController::class]);

        $request = new Request('GET', '/mixed/999', [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame(999, $data['id']);
        $this->assertSame('/mixed/999', $data['uri']);
    }
}

class RouteParamTestController
{
    #[Route('GET', '/users/{id}')]
    public function getUser(string $id): Response
    {
        return Response::json([
            'id' => $id,
            'type' => gettype($id)
        ]);
    }

    #[Route('GET', '/posts/{id}')]
    public function getPost(int $id): Response
    {
        return Response::json([
            'id' => $id,
            'type' => gettype($id)
        ]);
    }

    #[Route('GET', '/users/{userId}/posts/{slug}')]
    public function getUserPost(int $userId, string $slug): Response
    {
        return Response::json([
            'userId' => $userId,
            'slug' => $slug
        ]);
    }

    #[Route('GET', '/static')]
    public function staticRoute(): Response
    {
        return Response::json(['result' => 'no-params']);
    }

    #[Route('GET', '/mixed/{id}')]
    public function mixedInjection(int $id, Request $request): Response
    {
        return Response::json([
            'id' => $id,
            'uri' => $request->uri
        ]);
    }
}
