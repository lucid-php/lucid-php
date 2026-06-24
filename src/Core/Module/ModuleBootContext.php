<?php

declare(strict_types=1);

namespace Core\Module;

use Core\Application;
use Core\Event\EventDispatcher;
use Core\GraphQL\GraphQLRegistry;
use Core\Pipeline\PipelineRegistry;

final class ModuleBootContext
{
    public function __construct(
        public readonly Application $app,
        public readonly EventDispatcher $events,
        public readonly GraphQLRegistry $graphql,
        public readonly PipelineRegistry $pipelines,
        public readonly MigrationPathRegistry $migrations,
    ) {
    }
}
