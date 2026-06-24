<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\Argument;
use Core\Attribute\ConsoleCommand;
use Core\Config\Config;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Container;
use Core\Router;
use Uri\Rfc3986\Uri;

/**
 * Show which route handles a given request.
 *
 * Usage:
 *   php console route:match GET /api/users/5
 */
#[ConsoleCommand(
    name: 'route:match',
    description: 'Show which controller handles a given METHOD and URI'
)]
class RouteMatchCommand implements CommandInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly Config $config
    ) {
    }

    public function execute(
        OutputInterface $output,
        #[Argument('method', 'HTTP method, e.g. GET')]
        string $method = 'GET',
        #[Argument('uri', 'Request path, e.g. /api/users/5')]
        string $uri = '/'
    ): int {
        $router = new Router($this->container);
        $router->registerControllers($this->config->getArray('controllers.list'));

        $path = (new Uri($uri))->getPath();
        $matched = $router->match(strtoupper($method), $path);

        if ($matched === null) {
            $output->warning('No route matches ' . strtoupper($method) . " {$path}");
            return 1;
        }

        $route = $matched['route'];
        $middleware = implode(', ', array_map([$this, 'shortName'], $route['middlewares'])) ?: '-';

        $output->success('Matched route:');
        $output->writeln('  Handler:    <comment>' . $route['controller'] . '@' . $route['method'] . '</comment>');
        $output->writeln('  Pattern:    <comment>' . $route['path'] . '</comment>');
        $output->writeln('  Middleware: <comment>' . $middleware . '</comment>');

        if ($matched['params'] !== []) {
            $output->writeln('  Params:');
            foreach ($matched['params'] as $name => $value) {
                $output->writeln("    {$name} = <comment>{$value}</comment>");
            }
        }

        return 0;
    }

    private function shortName(string $class): string
    {
        $position = strrpos($class, '\\');
        return $position === false ? $class : substr($class, $position + 1);
    }
}
