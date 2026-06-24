<?php

declare(strict_types=1);

namespace Tests\Core\Module;

use Core\Module\MigrationPathRegistry;
use PHPUnit\Framework\TestCase;

final class MigrationPathRegistryTest extends TestCase
{
    public function testAddsPathsExplicitly(): void
    {
        $registry = new MigrationPathRegistry();

        $registry->addPath('/path/to/migrations');

        self::assertContains('/path/to/migrations', $registry->getPaths());
    }

    public function testPreventsDuplicatePaths(): void
    {
        $registry = new MigrationPathRegistry();

        $registry->addPath('/path/to/migrations');
        $registry->addPath('/path/to/migrations');

        self::assertCount(1, $registry->getPaths());
    }

    public function testReturnsPathsInOrder(): void
    {
        $registry = new MigrationPathRegistry();

        $registry->addPath('/path/1');
        $registry->addPath('/path/2');
        $registry->addPath('/path/3');

        $paths = $registry->getPaths();

        self::assertSame('/path/1', $paths[0]);
        self::assertSame('/path/2', $paths[1]);
        self::assertSame('/path/3', $paths[2]);
    }
}
