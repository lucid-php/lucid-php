<?php

declare(strict_types=1);

namespace Tests\Unit\GraphQL;

use Core\GraphQL\DuplicateGraphQLMutationException;
use Core\GraphQL\DuplicateGraphQLQueryException;
use Core\GraphQL\DuplicateGraphQLTypeException;
use Core\GraphQL\GraphQLMutation;
use Core\GraphQL\GraphQLQuery;
use Core\GraphQL\GraphQLRegistry;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;
use Tests\Support\GraphQL\EchoMutationResolver;
use Tests\Support\GraphQL\HealthQueryResolver;

final class GraphQLRegistryTest extends TestCase
{
    public function testRegistersTypeQueryAndMutation(): void
    {
        $registry = new GraphQLRegistry();

        $registry->registerType('HealthType', Type::string());
        $registry->registerQuery(new GraphQLQuery(
            name: 'health',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: HealthQueryResolver::class,
        ));
        $registry->registerMutation(new GraphQLMutation(
            name: 'echo',
            type: Type::nonNull(Type::string()),
            args: [
                'message' => ['type' => Type::nonNull(Type::string())],
            ],
            resolver: EchoMutationResolver::class,
        ));

        self::assertArrayHasKey('HealthType', $registry->types());
        self::assertArrayHasKey('health', $registry->queries());
        self::assertArrayHasKey('echo', $registry->mutations());
    }

    public function testRejectsDuplicateTypeName(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerType('HealthType', Type::string());

        $this->expectException(DuplicateGraphQLTypeException::class);
        $registry->registerType('HealthType', Type::string());
    }

    public function testRejectsDuplicateQueryName(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'health',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: HealthQueryResolver::class,
        ));

        $this->expectException(DuplicateGraphQLQueryException::class);
        $registry->registerQuery(new GraphQLQuery(
            name: 'health',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: HealthQueryResolver::class,
        ));
    }

    public function testRejectsDuplicateMutationName(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerMutation(new GraphQLMutation(
            name: 'echo',
            type: Type::nonNull(Type::string()),
            args: [
                'message' => ['type' => Type::nonNull(Type::string())],
            ],
            resolver: EchoMutationResolver::class,
        ));

        $this->expectException(DuplicateGraphQLMutationException::class);
        $registry->registerMutation(new GraphQLMutation(
            name: 'echo',
            type: Type::nonNull(Type::string()),
            args: [
                'message' => ['type' => Type::nonNull(Type::string())],
            ],
            resolver: EchoMutationResolver::class,
        ));
    }

    public function testPreservesDeterministicRegistrationOrder(): void
    {
        $registry = new GraphQLRegistry();

        $registry->registerQuery(new GraphQLQuery(
            name: 'first',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: HealthQueryResolver::class,
        ));
        $registry->registerQuery(new GraphQLQuery(
            name: 'second',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: HealthQueryResolver::class,
        ));

        self::assertSame(['first', 'second'], array_keys($registry->queries()));
    }
}
