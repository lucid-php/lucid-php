<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entity\User;
use App\Middleware\AuthMiddleware;
use App\Repository\TokenRepository;
use Core\Http\UnauthorizedException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;

class AuthMiddlewareTest extends TestCase
{
    public function testUnauthorizedWhenNoToken(): void
    {
        $tokenRepo = $this->createStub(TokenRepository::class);
        $middleware = new AuthMiddleware($tokenRepo);
        
        $request = new Request('GET', '/', server: []);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Missing or Invalid Token');

        $middleware->process($request, $handler);
    }

    public function testUnauthorizedWhenInvalidToken(): void
    {
        $tokenRepo = $this->createStub(TokenRepository::class);
        $tokenRepo->method('findUserByToken')->willReturn(null);
        
        $middleware = new AuthMiddleware($tokenRepo);
        
        $request = new Request('GET', '/', server: ['HTTP_AUTHORIZATION' => 'Bearer invalid-token']);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Invalid Token');

        $middleware->process($request, $handler);
    }

    public function testAuthenticatedWhenValidToken(): void
    {
        $user = new User(1, 'John Doe', 'john@example.com', 'hashed');
        
        $tokenRepo = $this->createStub(TokenRepository::class);
        $tokenRepo->method('findUserByToken')->willReturn($user);
        
        $middleware = new AuthMiddleware($tokenRepo);
        
        $request = new Request('GET', '/', server: ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (Request $req) use ($user) {
                return $req->getAttribute('user') === $user;
            }))
            ->willReturn(Response::json(['status' => 'ok']));

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->status);
    }
}
