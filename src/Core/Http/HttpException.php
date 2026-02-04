<?php

declare(strict_types=1);

namespace Core\Http;

use Exception;

abstract class HttpException extends Exception
{
    public function __construct(
        string $message = '',
        public readonly int $statusCode = 500,
        public readonly array $headers = []
    ) {
        parent::__construct($message);
    }
}
