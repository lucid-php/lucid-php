<?php

declare(strict_types=1);

namespace Tests\Support\GraphQL;

use Core\GraphQL\GraphQLContext;
use Core\GraphQL\GraphQLResolverInterface;

final readonly class ContextQueryResolver implements GraphQLResolverInterface
{
    public function resolve(mixed $root, array $args, GraphQLContext $context): string
    {
        return $context->requestId ?? 'none';
    }
}
