<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Container;
use Core\Router;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\ApiResponse;
use Core\Attribute\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    #[Test]
    public function it_returns_404_if_route_not_found(): void
    {
        $container = new class extends Container {
            public function get(string $class): object { return new \stdClass(); }
        };
        $router = new Router($container);
        
        $request = new Request('GET', '/non-existent');
        $response = $router->dispatch($request);
        
        $this->assertEquals(404, $response->status);
        $this->assertEquals('404 Not Found', $response->content);
    }

    #[Test]
    public function it_dispatches_to_controller(): void
    {
        $controller = new class {
            #[Route('GET', '/test')]
            public function index(): string {
                return 'Hello Test';
            }
        };
        $controllerClass = get_class($controller);

        $container = new class($controller, $controllerClass) extends Container {
            public function __construct(private object $controller, private string $controllerClass) {}
            
            public function get(string $class): object {
                if ($class === $this->controllerClass) return $this->controller;
                return parent::get($class);
            }
        };

        $router = new Router($container);
        $router->registerControllers([$controllerClass]);
        
        $request = new Request('GET', '/test');
        $response = $router->dispatch($request);
        
        $this->assertEquals(200, $response->status);
        $this->assertEquals('Hello Test', $response->content);
    }

    #[Test]
    public function it_converts_api_response_to_http_response(): void
    {
        $controller = new class {
            #[Route('GET', '/api/users')]
            public function index(): ApiResponse {
                return ApiResponse::success(
                    data: ['users' => [['id' => 1, 'name' => 'John']]],
                    message: 'Users retrieved'
                );
            }
        };
        $controllerClass = get_class($controller);

        $container = new class($controller, $controllerClass) extends Container {
            public function __construct(private object $controller, private string $controllerClass) {}
            
            public function get(string $class): object {
                if ($class === $this->controllerClass) return $this->controller;
                return parent::get($class);
            }
        };

        $router = new Router($container);
        $router->registerControllers([$controllerClass]);
        
        $request = new Request('GET', '/api/users');
        $response = $router->dispatch($request);
        
        $this->assertEquals(200, $response->status);
        $this->assertEquals('application/json', $response->headers['Content-Type']);
        
        $content = json_decode($response->content, true);
        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
        $this->assertEquals(['users' => [['id' => 1, 'name' => 'John']]], $content['data']);
        $this->assertEquals('Users retrieved', $content['message']);
    }

    #[Test]
    public function it_converts_api_error_response_with_correct_status(): void
    {
        $controller = new class {
            #[Route('GET', '/api/users/{id}')]
            public function show(int $id): ApiResponse {
                return ApiResponse::notFound('User not found');
            }
        };
        $controllerClass = get_class($controller);

        $container = new class($controller, $controllerClass) extends Container {
            public function __construct(private object $controller, private string $controllerClass) {}
            
            public function get(string $class): object {
                if ($class === $this->controllerClass) return $this->controller;
                return parent::get($class);
            }
        };

        $router = new Router($container);
        $router->registerControllers([$controllerClass]);
        
        $request = new Request('GET', '/api/users/999');
        $response = $router->dispatch($request);
        
        $this->assertEquals(404, $response->status);
        
        $content = json_decode($response->content, true);
        $this->assertFalse($content['success']);
        $this->assertEquals('User not found', $content['message']);
    }
}
