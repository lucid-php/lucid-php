<?php

declare(strict_types=1);

namespace Tests\Unit\GraphQL;

use Core\Container;
use Core\GraphQL\GraphQLContext;
use Core\GraphQL\GraphQLMutation;
use Core\GraphQL\GraphQLQuery;
use Core\GraphQL\GraphQLRegistry;
use Core\GraphQL\GraphQLSchemaFactory;
use Core\GraphQL\InvalidGraphQLResolverException;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;
use Tests\Support\GraphQL\ContainerBackedQueryResolver;
use Tests\Support\GraphQL\ContextQueryResolver;
use Tests\Support\GraphQL\EchoMutationResolver;
use Tests\Support\GraphQL\HealthQueryResolver;
use Tests\Support\GraphQL\InvalidResolver;

final class GraphQLSchemaFactoryTest extends TestCase
{
    public function testBuildsSchemaWithRegisteredQuery(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'health',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: HealthQueryResolver::class,
        ));

        $factory = new GraphQLSchemaFactory($registry, new Container());
        $result = GraphQL::executeQuery(
            $factory->create(),
            '{ health }',
            null,
            new GraphQLContext(),
        );

        self::assertSame(['health' => 'ok'], $result->toArray()['data']);
    }

    public function testBuildsSchemaWithRegisteredMutation(): void
    {
        $registry = new GraphQLRegistry();
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

        $factory = new GraphQLSchemaFactory($registry, new Container());
        $result = GraphQL::executeQuery(
            $factory->create(),
            'mutation { echo(message: "hello") }',
            null,
            new GraphQLContext(),
        );

        self::assertSame(['echo' => 'hello'], $result->toArray()['data']);
    }

    public function testResolvesResolverThroughContainer(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'containerValue',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: ContainerBackedQueryResolver::class,
        ));

        $factory = new GraphQLSchemaFactory($registry, new Container());
        $result = GraphQL::executeQuery(
            $factory->create(),
            '{ containerValue }',
            null,
            new GraphQLContext(),
        );

        self::assertSame(['containerValue' => 'from-container'], $result->toArray()['data']);
    }

    public function testRejectsResolverThatDoesNotImplementInterface(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'broken',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: InvalidResolver::class,
        ));

        $factory = new GraphQLSchemaFactory($registry, new Container());

        $this->expectException(InvalidGraphQLResolverException::class);
        GraphQL::executeQuery(
            $factory->create(),
            '{ broken }',
            null,
            new GraphQLContext(),
        )->toArray();
    }

    public function testPassesContextToResolver(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'requestId',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: ContextQueryResolver::class,
        ));

        $factory = new GraphQLSchemaFactory($registry, new Container());
        $context = new GraphQLContext(requestId: 'req-123');
        $result = GraphQL::executeQuery($factory->create(), '{ requestId }', null, $context);

        self::assertSame(['requestId' => 'req-123'], $result->toArray()['data']);
    }

    public function testSupportsVariables(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'echo',
            type: Type::nonNull(Type::string()),
            args: [
                'message' => ['type' => Type::nonNull(Type::string())],
            ],
            resolver: EchoMutationResolver::class,
        ));

        $factory = new GraphQLSchemaFactory($registry, new Container());
        $result = GraphQL::executeQuery(
            schema: $factory->create(),
            source: 'query Echo($message: String!) { echo(message: $message) }',
            rootValue: null,
            contextValue: new GraphQLContext(),
            variableValues: ['message' => 'from-var'],
            operationName: 'Echo',
        );

        self::assertSame(['echo' => 'from-var'], $result->toArray()['data']);
    }
}
