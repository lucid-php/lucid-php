<?php

declare(strict_types=1);

namespace Core\Module;

final class MigrationPathRegistry
{
    /**
     * @var list<string>
     */
    private array $paths = [];

    public function addPath(string $path): void
    {
        if (!in_array($path, $this->paths, true)) {
            $this->paths[] = $path;
        }
    }

    /**
     * @return list<string>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }
}
