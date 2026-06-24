<?php

declare(strict_types=1);

namespace Tests\Support\GraphQL;

final readonly class ResolverDependency
{
    public function value(): string
    {
        return 'from-container';
    }
}
