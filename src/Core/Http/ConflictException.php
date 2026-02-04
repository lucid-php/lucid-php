<?php

declare(strict_types=1);

namespace Core\Http;

class ConflictException extends HttpException
{
    public function __construct(string $message = 'Conflict')
    {
        parent::__construct($message, 409);
    }
}
