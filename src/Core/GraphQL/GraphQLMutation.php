<?php

declare(strict_types=1);

namespace Core\GraphQL;

use GraphQL\Type\Definition\OutputType;

final readonly class GraphQLMutation
{
    /**
     * @param array<string, mixed> $args
     * @param class-string<GraphQLResolverInterface> $resolver
     */
    public function __construct(
        public string $name,
        public OutputType $type,
        public array $args,
        public string $resolver,
        public ?string $description = null,
    ) {
    }
}
