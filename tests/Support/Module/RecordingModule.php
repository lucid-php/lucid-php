<?php

declare(strict_types=1);

namespace Tests\Support\Module;

use Core\Container;
use Core\Module\ModuleBootContext;
use Core\Module\ModuleInterface;

final class RecordingModule implements ModuleInterface
{
    /**
     * @var list<string>
     */
    public static array $calls = [];

    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        private readonly string $moduleName,
        private readonly array $dependencies = [],
    ) {
    }

    public function name(): string
    {
        return $this->moduleName;
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function dependsOn(): array
    {
        return $this->dependencies;
    }

    public function register(Container $container): void
    {
        self::$calls[] = 'register:' . $this->moduleName;
    }

    public function boot(ModuleBootContext $context): void
    {
        self::$calls[] = 'boot:' . $this->moduleName;
    }
}
