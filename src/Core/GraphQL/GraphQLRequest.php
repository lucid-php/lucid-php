<?php

declare(strict_types=1);

namespace Core\GraphQL;

final readonly class GraphQLRequest
{
    /**
     * @param array<string, mixed>|null $variables
     */
    public function __construct(
        public string $query,
        public ?array $variables = null,
        public ?string $operationName = null,
    ) {
    }
}
