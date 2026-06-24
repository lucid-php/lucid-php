<?php

declare(strict_types=1);

namespace Core\GraphQL;

use GraphQL\Type\Definition\Type;

final class GraphQLRegistry
{
    /**
     * @var array<string, Type|callable():Type>
     */
    private array $types = [];

    /**
     * @var array<string, GraphQLQuery>
     */
    private array $queries = [];

    /**
     * @var array<string, GraphQLMutation>
     */
    private array $mutations = [];

    /**
     * @param Type|callable():Type $type
     */
    public function registerType(string $name, Type|callable $type): void
    {
        if (isset($this->types[$name])) {
            throw new DuplicateGraphQLTypeException(
                sprintf('GraphQL type "%s" is already registered.', $name)
            );
        }

        $this->types[$name] = $type;
    }

    public function registerQuery(GraphQLQuery $query): void
    {
        if (isset($this->queries[$query->name])) {
            throw new DuplicateGraphQLQueryException(
                sprintf('GraphQL query "%s" is already registered.', $query->name)
            );
        }

        $this->queries[$query->name] = $query;
    }

    public function registerMutation(GraphQLMutation $mutation): void
    {
        if (isset($this->mutations[$mutation->name])) {
            throw new DuplicateGraphQLMutationException(
                sprintf('GraphQL mutation "%s" is already registered.', $mutation->name)
            );
        }

        $this->mutations[$mutation->name] = $mutation;
    }

    /**
     * @return array<string, Type|callable():Type>
     */
    public function types(): array
    {
        return $this->types;
    }

    /**
     * @return array<string, GraphQLQuery>
     */
    public function queries(): array
    {
        return $this->queries;
    }

    /**
     * @return array<string, GraphQLMutation>
     */
    public function mutations(): array
    {
        return $this->mutations;
    }

    /**
     * @return array<string, Type|callable():Type>
     */
    public function getTypes(): array
    {
        return $this->types();
    }

    /**
     * @return array<string, GraphQLQuery>
     */
    public function getQueries(): array
    {
        return $this->queries();
    }

    /**
     * @return array<string, GraphQLMutation>
     */
    public function getMutations(): array
    {
        return $this->mutations();
    }
}
