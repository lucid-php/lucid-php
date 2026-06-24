<?php

declare(strict_types=1);

namespace Tests\Core\Module;

use Core\Container;
use Core\Module\CircularModuleDependencyException;
use Core\Module\ModuleAlreadyRegisteredException;
use Core\Module\ModuleBootContext;
use Core\Module\ModuleInterface;
use Core\Module\ModuleNotFoundException;
use Core\Module\ModuleRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleRegistryTest extends TestCase
{
    public function testRegistersModulesExplicitly(): void
    {
        $module = new TestModule('test.module', '1.0.0', []);

        $registry = new ModuleRegistry([$module]);

        self::assertCount(1, $registry->getSorted());
    }

    public function testThrowsOnDuplicateModuleName(): void
    {
        $module1 = new TestModule('test.module', '1.0.0', []);
        $module2 = new TestModule('test.module', '2.0.0', []);

        $this->expectException(ModuleAlreadyRegisteredException::class);
        $this->expectExceptionMessage('Module "test.module" is already registered');

        new ModuleRegistry([$module1, $module2]);
    }

    public function testThrowsOnMissingDependency(): void
    {
        $module = new TestModule('test.module', '1.0.0', ['missing.dependency']);

        $this->expectException(ModuleNotFoundException::class);
        $this->expectExceptionMessage('depends on missing module');

        new ModuleRegistry([$module]);
    }

    public function testDetectsCircularDependencies(): void
    {
        $module1 = new TestModule('mod1', '1.0.0', ['mod2']);
        $module2 = new TestModule('mod2', '1.0.0', ['mod1']);

        $registry = new ModuleRegistry([$module1, $module2]);

        $this->expectException(CircularModuleDependencyException::class);

        $registry->getSorted();
    }

    public function testSortsModulesInDependencyOrder(): void
    {
        $moduleA = new TestModule('a', '1.0.0', []);
        $moduleB = new TestModule('b', '1.0.0', ['a']);
        $moduleC = new TestModule('c', '1.0.0', ['b']);

        $registry = new ModuleRegistry([$moduleC, $moduleA, $moduleB]);

        $sorted = $registry->getSorted();

        self::assertSame('a', $sorted[0]->name());
        self::assertSame('b', $sorted[1]->name());
        self::assertSame('c', $sorted[2]->name());
    }

    public function testHandlesComplexDependencyGraph(): void
    {
        $moduleA = new TestModule('a', '1.0.0', []);
        $moduleB = new TestModule('b', '1.0.0', ['a']);
        $moduleC = new TestModule('c', '1.0.0', ['a']);
        $moduleD = new TestModule('d', '1.0.0', ['b', 'c']);

        $registry = new ModuleRegistry([$moduleD, $moduleC, $moduleA, $moduleB]);

        $sorted = $registry->getSorted();

        self::assertSame('a', $sorted[0]->name());
        self::assertContains($sorted[1]->name(), ['b', 'c']);
        self::assertContains($sorted[2]->name(), ['b', 'c']);
        self::assertSame('d', $sorted[3]->name());
    }
}

final class TestModule implements ModuleInterface
{
    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        private string $moduleName,
        private string $version,
        private array $dependencies,
    ) {
    }

    public function name(): string
    {
        return $this->moduleName;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function dependsOn(): array
    {
        return $this->dependencies;
    }

    public function register(Container $container): void
    {
    }

    public function boot(ModuleBootContext $context): void
    {
    }
}
