<?php

declare(strict_types=1);

namespace Core\GraphQL;

interface GraphQLResolverInterface
{
    /**
     * @param array<string, mixed> $args
     */
    public function resolve(mixed $root, array $args, GraphQLContext $context): mixed;
}
