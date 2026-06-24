<?php

declare(strict_types=1);

namespace Core\GraphQL;

use Core\Container;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;

final readonly class GraphQLSchemaFactory
{
    public function __construct(
        private GraphQLRegistry $registry,
        private Container $container,
    ) {
    }

    public function create(): Schema
    {
        $queries = $this->registry->queries();
        if ($queries === []) {
            throw new GraphQLException('GraphQL schema requires at least one registered query.');
        }

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => $this->buildQueryFields(),
        ]);

        $mutations = $this->registry->mutations();
        $mutationType = $mutations === []
            ? null
            : new ObjectType([
                'name' => 'Mutation',
                'fields' => $this->buildMutationFields(),
            ]);

        return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
            'types' => array_values($this->registry->types()),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildQueryFields(): array
    {
        $fields = [];

        foreach ($this->registry->queries() as $query) {
            $this->assertValidResolverClass($query->resolver);
            $fields[$query->name] = [
                'type' => $query->type,
                'args' => $query->args,
                'description' => $query->description,
                'resolve' => $this->resolverCallable($query->resolver),
            ];
        }

        return $fields;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildMutationFields(): array
    {
        $fields = [];

        foreach ($this->registry->mutations() as $mutation) {
            $this->assertValidResolverClass($mutation->resolver);
            $fields[$mutation->name] = [
                'type' => $mutation->type,
                'args' => $mutation->args,
                'description' => $mutation->description,
                'resolve' => $this->resolverCallable($mutation->resolver),
            ];
        }

        return $fields;
    }

    /**
     * @param class-string<GraphQLResolverInterface> $resolverClass
     */
    private function resolverCallable(string $resolverClass): callable
    {
        return function (mixed $root, array $args, mixed $context) use ($resolverClass): mixed {
            if (!$context instanceof GraphQLContext) {
                throw new GraphQLException('Invalid GraphQL context.');
            }

            $resolver = $this->container->get($resolverClass);

            if (!$resolver instanceof GraphQLResolverInterface) {
                throw new InvalidGraphQLResolverException(sprintf(
                    'GraphQL resolver "%s" must implement %s.',
                    $resolverClass,
                    GraphQLResolverInterface::class
                ));
            }

            return $resolver->resolve($root, $args, $context);
        };
    }

    /**
     * @param class-string $resolverClass
     */
    private function assertValidResolverClass(string $resolverClass): void
    {
        if (!is_a($resolverClass, GraphQLResolverInterface::class, true)) {
            throw new InvalidGraphQLResolverException(sprintf(
                'GraphQL resolver "%s" must implement %s.',
                $resolverClass,
                GraphQLResolverInterface::class
            ));
        }
    }
}
