<?php

declare(strict_types=1);

namespace Tests\Unit\GraphQL;

use Core\Container;
use Core\GraphQL\GraphQLConfig;
use Core\GraphQL\GraphQLContextFactory;
use Core\GraphQL\GraphQLController;
use Core\GraphQL\GraphQLExecutor;
use Core\GraphQL\GraphQLMutation;
use Core\GraphQL\GraphQLQuery;
use Core\GraphQL\GraphQLRegistry;
use Core\GraphQL\GraphQLRequestFactory;
use Core\GraphQL\GraphQLResponseFormatter;
use Core\GraphQL\GraphQLSchemaFactory;
use Core\Http\Request;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;
use Tests\Support\GraphQL\EchoMutationResolver;
use Tests\Support\GraphQL\FailingQueryResolver;
use Tests\Support\GraphQL\HealthQueryResolver;

final class GraphQLControllerTest extends TestCase
{
    public function testInvalidJsonReturnsHttp400(): void
    {
        $controller = $this->createController();
        $response = $controller->execute(
            new Request('POST', '/graphql', body: [], jsonParseError: true)
        );

        self::assertSame(400, $response->status);
    }

    public function testMissingQueryReturnsHttp400(): void
    {
        $controller = $this->createController();
        $response = $controller->execute(new Request('POST', '/graphql', body: []));

        self::assertSame(400, $response->status);
    }

    public function testValidQueryReturnsHttp200(): void
    {
        $controller = $this->createController();
        $response = $controller->execute(new Request('POST', '/graphql', body: [
            'query' => '{ health }',
        ]));

        self::assertSame(200, $response->status);
        self::assertStringContainsString('"health":"ok"', $response->content);
    }

    public function testExecutionErrorReturnsHttp200WithErrors(): void
    {
        $registry = new GraphQLRegistry();
        $registry->registerQuery(new GraphQLQuery(
            name: 'fail',
            type: Type::nonNull(Type::string()),
            args: [],
            resolver: FailingQueryResolver::class,
        ));

        $controller = $this->createController($registry);
        $response = $controller->execute(new Request('POST', '/graphql', body: [
            'query' => '{ fail }',
        ]));

        self::assertSame(200, $response->status);
        self::assertStringContainsString('"errors"', $response->content);
    }

    public function testVariablesWorkThroughHttpEndpoint(): void
    {
        $controller = $this->createController();
        $response = $controller->execute(new Request('POST', '/graphql', body: [
            'query' => 'mutation Echo($message: String!) { echo(message: $message) }',
            'variables' => ['message' => 'hello'],
            'operationName' => 'Echo',
        ]));

        self::assertSame(200, $response->status);
        self::assertStringContainsString('"echo":"hello"', $response->content);
    }

    public function testOperationNameWorksThroughHttpEndpoint(): void
    {
        $controller = $this->createController();
        $response = $controller->execute(new Request('POST', '/graphql', body: [
            'query' => <<<'GQL'
query First { health }
query Second { health }
GQL,
            'operationName' => 'Second',
        ]));

        self::assertSame(200, $response->status);
        self::assertStringContainsString('"health":"ok"', $response->content);
    }

    private function createController(?GraphQLRegistry $registry = null): GraphQLController
    {
        $runtimeRegistry = $registry ?? new GraphQLRegistry();

        if ($registry === null) {
            $runtimeRegistry->registerQuery(new GraphQLQuery(
                name: 'health',
                type: Type::nonNull(Type::string()),
                args: [],
                resolver: HealthQueryResolver::class,
            ));
            $runtimeRegistry->registerMutation(new GraphQLMutation(
                name: 'echo',
                type: Type::nonNull(Type::string()),
                args: [
                    'message' => ['type' => Type::nonNull(Type::string())],
                ],
                resolver: EchoMutationResolver::class,
            ));
        }

        $executor = new GraphQLExecutor(
            new GraphQLSchemaFactory($runtimeRegistry, new Container()),
            new GraphQLResponseFormatter(new GraphQLConfig(debug: false)),
            new GraphQLConfig(debug: false),
        );

        return new GraphQLController(
            new GraphQLRequestFactory(),
            new GraphQLContextFactory(),
            $executor,
        );
    }
}
