<?php

declare(strict_types=1);

namespace Tests\Support\GraphQL;

final class InvalidResolver
{
    public function resolve(): string
    {
        return 'invalid';
    }
}
