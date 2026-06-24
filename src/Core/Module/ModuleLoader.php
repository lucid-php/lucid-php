<?php

declare(strict_types=1);

namespace Core\Module;

use Core\Application;
use Core\Container;
use Core\Event\EventDispatcher;
use Core\GraphQL\GraphQLRegistry;
use Core\Pipeline\PipelineRegistry;

final class ModuleLoader
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly Container $container,
        private readonly Application $app,
        private readonly EventDispatcher $events,
        private readonly GraphQLRegistry $graphql,
        private readonly PipelineRegistry $pipelines,
        private readonly MigrationPathRegistry $migrations,
    ) {
    }

    public function boot(): void
    {
        $sorted = $this->registry->getSorted();

        foreach ($sorted as $module) {
            $module->register($this->container);
        }

        $context = new ModuleBootContext(
            app: $this->app,
            events: $this->events,
            graphql: $this->graphql,
            pipelines: $this->pipelines,
            migrations: $this->migrations,
        );

        foreach ($sorted as $module) {
            $module->boot($context);
        }
    }
}
