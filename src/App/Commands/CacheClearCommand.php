<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\ConsoleCommand;
use Core\Cache\FileCache;
use Core\Config\Config;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;

/**
 * Clear the file-based cache.
 *
 * Usage:
 *   php console cache:clear
 */
#[ConsoleCommand(
    name: 'cache:clear',
    description: 'Clear the file cache'
)]
class CacheClearCommand implements CommandInterface
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function execute(OutputInterface $output): int
    {
        $driver = $this->config->getString('cache.default', 'file');

        if ($driver !== 'file') {
            $output->info("Cache driver is '{$driver}' (in-memory); nothing on disk to clear.");
            return 0;
        }

        $path = $this->config->getString('cache.drivers.file.path');
        (new FileCache($path))->clear();

        $output->success("File cache cleared: {$path}");

        return 0;
    }
}
