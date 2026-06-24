<?php

declare(strict_types=1);

namespace Core\Module;

use Core\Container;

interface ModuleInterface
{
    public function name(): string;

    public function version(): string;

    /**
     * @return list<string>
     */
    public function dependsOn(): array;

    public function register(Container $container): void;

    public function boot(ModuleBootContext $context): void;
}
