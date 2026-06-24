<?php

declare(strict_types=1);

namespace Tests\Unit\Module;

use Core\Application;
use Core\Event\EventDispatcher;
use Core\GraphQL\GraphQLConfig;
use Core\GraphQL\GraphQLContext;
use Core\GraphQL\GraphQLExecutor;
use Core\GraphQL\GraphQLRegistry;
use Core\GraphQL\GraphQLRequest;
use Core\GraphQL\GraphQLResponseFormatter;
use Core\GraphQL\GraphQLSchemaFactory;
use Core\Module\MigrationPathRegistry;
use Core\Module\ModuleLoader;
use Core\Module\ModuleRegistry;
use Core\Pipeline\PipelineRegistry;
use PHPUnit\Framework\TestCase;
use Tests\Support\Module\GraphQLTestModule;

final class GraphQLModuleIntegrationTest extends TestCase
{
    public function testRegisteredQueryIsExecutableAfterModuleLoaderRuns(): void
    {
        $executor = $this->bootModuleAndCreateExecutor();

        $result = $executor->execute(new GraphQLRequest('{ health }'), new GraphQLContext());

        self::assertSame(['health' => 'ok'], $result['data']);
    }

    public function testRegisteredMutationIsExecutableAfterModuleLoaderRuns(): void
    {
        $executor = $this->bootModuleAndCreateExecutor();

        $result = $executor->execute(
            new GraphQLRequest('mutation Echo($message: String!) { echo(message: $message) }', ['message' => 'hi'], 'Echo'),
            new GraphQLContext(),
        );

        self::assertSame(['echo' => 'hi'], $result['data']);
    }

    private function bootModuleAndCreateExecutor(): GraphQLExecutor
    {
        $app = new Application();
        $container = $app->getContainer();
        $events = new EventDispatcher($container);
        $graphql = new GraphQLRegistry();
        $pipelines = new PipelineRegistry();
        $migrations = new MigrationPathRegistry();

        $registry = new ModuleRegistry();
        $registry->register(new GraphQLTestModule());

        $loader = new ModuleLoader(
            registry: $registry,
            container: $container,
            app: $app,
            events: $events,
            graphql: $graphql,
            pipelines: $pipelines,
            migrations: $migrations,
        );
        $loader->boot();

        $executor = new GraphQLExecutor(
            new GraphQLSchemaFactory($graphql, $container),
            new GraphQLResponseFormatter(new GraphQLConfig(debug: false)),
            new GraphQLConfig(debug: false),
        );

        return $executor;
    }
}
