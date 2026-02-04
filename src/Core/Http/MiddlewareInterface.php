<?php

declare(strict_types=1);

namespace Core\Http;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response;
}
