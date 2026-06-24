<?php

declare(strict_types=1);

namespace Tests\Support\Module;

use Core\Container;
use Core\GraphQL\GraphQLMutation;
use Core\GraphQL\GraphQLQuery;
use Core\Module\ModuleBootContext;
use Core\Module\ModuleInterface;
use GraphQL\Type\Definition\Type;
use Tests\Support\GraphQL\EchoMutationResolver;
use Tests\Support\GraphQL\HealthQueryResolver;

final class GraphQLTestModule implements ModuleInterface
{
    public function name(): string
    {
        return 'tests.graphql';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function register(Container $container): void
    {
    }

    public function boot(ModuleBootContext $context): void
    {
        $context->graphql->registerQuery(new GraphQLQuery(
            name: 'health',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: HealthQueryResolver::class,
        ));

        $context->graphql->registerMutation(new GraphQLMutation(
            name: 'echo',
            type: Type::nonNull(Type::string()),
            args: [
                'message' => ['type' => Type::nonNull(Type::string())],
            ],
            resolver: EchoMutationResolver::class,
        ));
    }
}
