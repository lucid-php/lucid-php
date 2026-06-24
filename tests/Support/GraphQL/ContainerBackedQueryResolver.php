<?php

declare(strict_types=1);

namespace Tests\Support\GraphQL;

use Core\GraphQL\GraphQLContext;
use Core\GraphQL\GraphQLResolverInterface;

final readonly class ContainerBackedQueryResolver implements GraphQLResolverInterface
{
    public function __construct(
        private ResolverDependency $dependency,
    ) {
    }

    public function resolve(mixed $root, array $args, GraphQLContext $context): string
    {
        return $this->dependency->value();
    }
}
