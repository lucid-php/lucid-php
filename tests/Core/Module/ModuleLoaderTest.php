<?php

declare(strict_types=1);

namespace Tests\Core\Module;

use Core\Application;
use Core\Container;
use Core\Event\EventDispatcher;
use Core\GraphQL\GraphQLRegistry;
use Core\Module\MigrationPathRegistry;
use Core\Module\ModuleBootContext;
use Core\Pipeline\PipelineRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleLoaderTest extends TestCase
{
    public function testBootContextExposesAllRegistries(): void
    {
        $container = new Container();
        $app = new Application($container);
        $events = new EventDispatcher();
        $graphql = new GraphQLRegistry();
        $pipelines = new PipelineRegistry();
        $migrations = new MigrationPathRegistry();

        $context = new ModuleBootContext(
            app: $app,
            events: $events,
            graphql: $graphql,
            pipelines: $pipelines,
            migrations: $migrations,
        );

        self::assertSame($app, $context->app);
        self::assertSame($events, $context->events);
        self::assertSame($graphql, $context->graphql);
        self::assertSame($pipelines, $context->pipelines);
        self::assertSame($migrations, $context->migrations);
    }
}
