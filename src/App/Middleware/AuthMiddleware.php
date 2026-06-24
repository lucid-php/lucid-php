<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repository\TokenRepository;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\RequestHandlerInterface;
use Core\Http\Response;
use Core\Http\UnauthorizedException;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly TokenRepository $tokenRepository)
    {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authorization = $request->server['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($authorization, 'Bearer ')) {
            throw new UnauthorizedException('Missing or Invalid Token');
        }

        $token = substr($authorization, 7);
        $user = $this->tokenRepository->findUserByToken($token);

        if (!$user) {
            throw new UnauthorizedException('Invalid Token');
        }

        // Add user to request attributes
        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
    }
}
