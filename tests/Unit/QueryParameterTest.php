<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attribute\QueryParam;
use Core\Attribute\Route;
use Core\Container;
use Core\Http\Request;
use Core\Http\Response;
use Core\Router;
use PHPUnit\Framework\TestCase;

class QueryParameterTest extends TestCase
{
    private Container $container;
    private Router $router;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->router = new Router($this->container);
    }

    public function test_it_injects_single_query_parameter(): void
    {
        $this->router->registerControllers([QueryParamTestController::class]);

        $request = new Request('GET', '/search', ['q' => 'test'], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame('test', $data['query']);
    }

    public function test_it_casts_query_parameter_to_int(): void
    {
        $this->router->registerControllers([QueryParamTestController::class]);

        $request = new Request('GET', '/paginate', ['page' => '5'], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame(5, $data['page']);
        $this->assertSame('integer', $data['type']);
    }

    public function test_it_uses_default_value_when_query_param_missing(): void
    {
        $this->router->registerControllers([QueryParamTestController::class]);

        $request = new Request('GET', '/paginate', [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame(1, $data['page']);
    }

    public function test_it_handles_multiple_query_parameters(): void
    {
        $this->router->registerControllers([QueryParamTestController::class]);

        $request = new Request('GET', '/filter', ['status' => 'active', 'limit' => '10'], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame('active', $data['status']);
        $this->assertSame(10, $data['limit']);
    }

    public function test_it_handles_bool_query_parameters(): void
    {
        $this->router->registerControllers([QueryParamTestController::class]);

        $request = new Request('GET', '/toggle', ['enabled' => 'true'], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertTrue($data['enabled']);
        $this->assertSame('boolean', $data['type']);
    }

    public function test_it_handles_float_query_parameters(): void
    {
        $this->router->registerControllers([QueryParamTestController::class]);

        $request = new Request('GET', '/price', ['min' => '19.99'], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame(19.99, $data['min']);
        $this->assertSame('double', $data['type']);
    }

    public function test_query_params_work_with_route_params(): void
    {
        $this->router->registerControllers([QueryParamTestController::class]);

        $request = new Request('GET', '/users/123', ['sort' => 'name'], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame(123, $data['id']);
        $this->assertSame('name', $data['sort']);
    }

    public function test_query_params_work_with_request_injection(): void
    {
        $this->router->registerControllers([QueryParamTestController::class]);

        $request = new Request('GET', '/mixed?page=2', ['page' => '2'], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame(2, $data['page']);
        $this->assertSame('/mixed?page=2', $data['uri']);
    }

    public function test_query_param_without_attribute_uses_default(): void
    {
        $this->router->registerControllers([QueryParamTestController::class]);

        $request = new Request('GET', '/no-attribute', ['ignored' => 'value'], [], []);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->content, true);
        $this->assertSame('default', $data['result']);
    }
}

class QueryParamTestController
{
    #[Route('GET', '/search')]
    public function search(#[QueryParam] string $q): Response
    {
        return Response::json(['query' => $q]);
    }

    #[Route('GET', '/paginate')]
    public function paginate(#[QueryParam] int $page = 1): Response
    {
        return Response::json([
            'page' => $page,
            'type' => gettype($page)
        ]);
    }

    #[Route('GET', '/filter')]
    public function filter(#[QueryParam] string $status = 'all', #[QueryParam] int $limit = 20): Response
    {
        return Response::json([
            'status' => $status,
            'limit' => $limit
        ]);
    }

    #[Route('GET', '/toggle')]
    public function toggle(#[QueryParam] bool $enabled = false): Response
    {
        return Response::json([
            'enabled' => $enabled,
            'type' => gettype($enabled)
        ]);
    }

    #[Route('GET', '/price')]
    public function price(#[QueryParam] float $min = 0.0): Response
    {
        return Response::json([
            'min' => $min,
            'type' => gettype($min)
        ]);
    }

    #[Route('GET', '/users/{id}')]
    public function getUserPosts(int $id, #[QueryParam] string $sort = 'id'): Response
    {
        return Response::json([
            'id' => $id,
            'sort' => $sort
        ]);
    }

    #[Route('GET', '/mixed')]
    public function mixedInjection(#[QueryParam] int $page, Request $request): Response
    {
        return Response::json([
            'page' => $page,
            'uri' => $request->uri
        ]);
    }

    #[Route('GET', '/no-attribute')]
    public function noAttribute(string $ignored = 'default'): Response
    {
        return Response::json(['result' => $ignored]);
    }
}
