<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTO\LoginDTO;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Core\Attribute\RateLimit;
use Core\Attribute\Route;
use Core\Attribute\RoutePrefix;
use Core\Http\Response;
use Core\Http\UnauthorizedException;

#[RoutePrefix('/auth')]
class AuthController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TokenRepository $tokenRepository
    ) {
    }

    // Very strict rate limit for login attempts (security)
    #[Route('POST', '/login')]
    #[RateLimit(requests: 5, window: 60)]
    public function login(LoginDTO $data): Response
    {
        $user = $this->userRepository->findByEmail($data->email);

        if (!$user || !password_verify($data->password, $user->password)) {
            throw new UnauthorizedException('Invalid credentials');
        }

        $token = $this->tokenRepository->createToken($user->id);

        return Response::json([
            'message' => 'Login successful',
            'token' => $token,
        ]);
    }
}
