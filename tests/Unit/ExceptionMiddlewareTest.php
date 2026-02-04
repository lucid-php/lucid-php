<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\ExceptionHandler;
use Core\Http\NotFoundException;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;
use Core\Middleware\ExceptionMiddleware;
use Exception;
use PHPUnit\Framework\TestCase;

class ExceptionMiddlewareTest extends TestCase
{
    public function testPassesThroughWhenNoException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $middleware = new ExceptionMiddleware($handler);
        
        $request = new Request('GET', '/test', [], [], []);
        $expectedResponse = Response::json(['success' => true]);
        
        $requestHandler = new class($expectedResponse) implements RequestHandlerInterface {
            public function __construct(private Response $response) {}
            public function handle(Request $request): Response {
                return $this->response;
            }
        };
        
        $response = $middleware->process($request, $requestHandler);
        
        $this->assertSame($expectedResponse, $response);
    }

    public function testCatchesException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $middleware = new ExceptionMiddleware($handler);
        
        $request = new Request('GET', '/test', [], [], []);
        
        $requestHandler = new class implements RequestHandlerInterface {
            public function handle(Request $request): Response {
                throw new NotFoundException('Resource not found');
            }
        };
        
        $response = $middleware->process($request, $requestHandler);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(404, $response->status);
        
        $data = json_decode($response->content, true);
        $this->assertSame('Not Found', $data['error']);
        $this->assertSame('Resource not found', $data['message']);
    }

    public function testCatchesGenericException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $middleware = new ExceptionMiddleware($handler);
        
        $request = new Request('GET', '/test', [], [], []);
        
        $requestHandler = new class implements RequestHandlerInterface {
            public function handle(Request $request): Response {
                throw new Exception('Something broke');
            }
        };
        
        $response = $middleware->process($request, $requestHandler);
        
        $this->assertSame(500, $response->status);
        
        $data = json_decode($response->content, true);
        $this->assertSame('Internal Server Error', $data['error']);
    }

    public function testDebugModeIncludesDetails(): void
    {
        $handler = new ExceptionHandler(debug: true);
        $middleware = new ExceptionMiddleware($handler);
        
        $request = new Request('GET', '/test', [], [], []);
        
        $requestHandler = new class implements RequestHandlerInterface {
            public function handle(Request $request): Response {
                throw new Exception('Debug exception');
            }
        };
        
        $response = $middleware->process($request, $requestHandler);
        
        $data = json_decode($response->content, true);
        $this->assertArrayHasKey('exception', $data);
        $this->assertArrayHasKey('file', $data);
        $this->assertArrayHasKey('line', $data);
        $this->assertArrayHasKey('trace', $data);
    }
}
