<?php

declare(strict_types=1);

namespace Tests\Unit\Module;

use Core\Application;
use Core\Event\EventDispatcher;
use Core\GraphQL\GraphQLRegistry;
use Core\Module\MigrationPathRegistry;
use Core\Module\ModuleLoader;
use Core\Module\ModuleRegistry;
use Core\Pipeline\PipelineRegistry;
use PHPUnit\Framework\TestCase;
use Tests\Support\Module\RecordingModule;

final class ModuleLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        RecordingModule::$calls = [];
    }

    public function testLoaderRunsAllRegistersBeforeAnyBoot(): void
    {
        $app = new Application();
        $container = $app->getContainer();
        $registry = new ModuleRegistry([
            new RecordingModule('module.base'),
            new RecordingModule('module.child', ['module.base']),
        ]);

        $loader = new ModuleLoader(
            registry: $registry,
            container: $container,
            app: $app,
            events: new EventDispatcher($container),
            graphql: new GraphQLRegistry(),
            pipelines: new PipelineRegistry(),
            migrations: new MigrationPathRegistry(),
        );

        $loader->boot();

        self::assertSame(
            [
                'register:module.base',
                'register:module.child',
                'boot:module.base',
                'boot:module.child',
            ],
            RecordingModule::$calls
        );
    }
}
