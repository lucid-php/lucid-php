<?php

declare(strict_types=1);

namespace Core\GraphQL;

final readonly class GraphQLConfig
{
    public function __construct(
        public bool $debug = false,
        public bool $introspectionEnabled = true,
        public ?int $maxDepth = null,
        public ?int $maxComplexity = null,
    ) {
    }
}
