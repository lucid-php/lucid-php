<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Attribute\Route;
use Core\Http\Request;
use Core\Http\Response;

class HomeController
{
    #[Route('GET', '/')]
    public function index(): string
    {
        return "Welcome to the Strict Framework! running on PHP " . PHP_VERSION;
    }

    #[Route('GET', '/api/ping')]
    public function ping(Request $request): Response
    {
        return Response::json([
            'status' => 'ok',
            'timestamp' => time(),
            'query' => $request->query,
        ]);
    }
}
