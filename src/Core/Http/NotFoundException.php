<?php

declare(strict_types=1);

namespace Core\Http;

class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message, 404);
    }
}
