<?php

declare(strict_types=1);

namespace Tests\Unit\GraphQL;

use Core\Container;
use Core\GraphQL\GraphQLConfig;
use Core\GraphQL\GraphQLContext;
use Core\GraphQL\GraphQLExecutor;
use Core\GraphQL\GraphQLMutation;
use Core\GraphQL\GraphQLQuery;
use Core\GraphQL\GraphQLRegistry;
use Core\GraphQL\GraphQLRequest;
use Core\GraphQL\GraphQLResponseFormatter;
use Core\GraphQL\GraphQLSchemaFactory;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;
use Tests\Support\GraphQL\EchoMutationResolver;
use Tests\Support\GraphQL\FailingQueryResolver;
use Tests\Support\GraphQL\HealthQueryResolver;

final class GraphQLExecutorTest extends TestCase
{
    public function testExecutesSimpleQuery(): void
    {
        $executor = $this->createExecutor(new GraphQLConfig(debug: false));
        $response = $executor->execute(new GraphQLRequest('{ health }'), new GraphQLContext());

        self::assertSame(['health' => 'ok'], $response['data']);
    }

    public function testExecutesMutation(): void
    {
        $executor = $this->createExecutor(new GraphQLConfig(debug: false));
        $response = $executor->execute(
            new GraphQLRequest('mutation { echo(message: "hi") }'),
            new GraphQLContext(),
        );

        self::assertSame(['echo' => 'hi'], $response['data']);
    }

    public function testReturnsErrorsInConsistentShape(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'fail',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: FailingQueryResolver::class,
        ));

        $executor = $this->buildExecutor($registry, new GraphQLConfig(debug: false));
        $response = $executor->execute(new GraphQLRequest('{ fail }'), new GraphQLContext());

        self::assertArrayHasKey('errors', $response);
        self::assertArrayHasKey('message', $response['errors'][0]);
    }

    public function testMasksInternalErrorsWhenDebugIsFalse(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'fail',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: FailingQueryResolver::class,
        ));

        $executor = $this->buildExecutor($registry, new GraphQLConfig(debug: false));
        $response = $executor->execute(new GraphQLRequest('{ fail }'), new GraphQLContext());

        self::assertSame('Internal server error.', $response['errors'][0]['message']);
    }

    public function testIncludesDebugDetailsWhenDebugIsTrue(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'fail',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: FailingQueryResolver::class,
        ));

        $executor = $this->buildExecutor($registry, new GraphQLConfig(debug: true));
        $response = $executor->execute(new GraphQLRequest('{ fail }'), new GraphQLContext());

        self::assertArrayHasKey('debug', $response['errors'][0]);
    }

    private function createExecutor(GraphQLConfig $config): GraphQLExecutor
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

        return $this->buildExecutor($registry, $config);
    }

    private function buildExecutor(GraphQLRegistry $registry, GraphQLConfig $config): GraphQLExecutor
    {
        $schemaFactory = new GraphQLSchemaFactory($registry, new Container());
        $formatter = new GraphQLResponseFormatter($config);

        return new GraphQLExecutor($schemaFactory, $formatter, $config);
    }
}
