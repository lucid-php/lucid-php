<?php

declare(strict_types=1);

namespace Core\GraphQL;

final readonly class GraphQLContext
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public ?string $requestId = null,
        public ?object $user = null,
        public array $attributes = [],
    ) {
    }
}
