<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\Argument;
use Core\Attribute\ConsoleCommand;
use Core\Config\Config;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;

/**
 * Print resolved configuration. Sensitive values are masked.
 *
 * Usage:
 *   php console config:show            # all config files
 *   php console config:show database   # one file
 */
#[ConsoleCommand(
    name: 'config:show',
    description: 'Print resolved configuration (secrets masked)'
)]
class ConfigShowCommand implements CommandInterface
{
    private const SENSITIVE = '/pass|secret|key|token/i';

    public function __construct(
        private readonly Config $config
    ) {
    }

    public function execute(
        OutputInterface $output,
        #[Argument('file', 'Config file to show (omit for all)', required: false)]
        string $file = ''
    ): int {
        $files = $file !== '' ? [$file] : $this->allConfigFiles();

        if ($files === []) {
            $output->warning('No config files found.');
            return 0;
        }

        foreach ($files as $name) {
            $output->info($name);
            foreach ($this->flatten($this->config->all($name)) as $key => $value) {
                $output->writeln("  {$key} = <comment>{$value}</comment>");
            }
            $output->writeln('');
        }

        return 0;
    }

    /**
     * @return array<int, string>
     */
    private function allConfigFiles(): array
    {
        $files = glob(dirname(__DIR__, 3) . '/config/*.php') ?: [];

        return array_map(fn (string $path): string => basename($path, '.php'), $files);
    }

    /**
     * Flatten a nested config array to dot-keys, masking sensitive values.
     *
     * @param array<mixed> $config
     * @return array<string, string>
     */
    private function flatten(array $config, string $prefix = ''): array
    {
        $flat = [];

        foreach ($config as $key => $value) {
            $dotKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $flat += $this->flatten($value, $dotKey);
                continue;
            }

            $flat[$dotKey] = $this->render($key, $value);
        }

        return $flat;
    }

    private function render(int|string $key, mixed $value): string
    {
        if (is_string($key) && preg_match(self::SENSITIVE, $key) === 1 && $value !== null && $value !== '') {
            return '***';
        }

        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            is_scalar($value) => (string) $value,
            default => get_debug_type($value),
        };
    }
}
