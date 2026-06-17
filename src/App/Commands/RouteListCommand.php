<?php

declare(strict_types=1);

namespace App\Commands;

use Core\Attribute\ConsoleCommand;
use Core\Config\Config;
use Core\Console\CommandInterface;
use Core\Console\OutputInterface;
use Core\Container;
use Core\Router;

/**
 * List every registered route.
 *
 * Reflects the controllers in config/controllers.php (no instantiation) and
 * prints their #[Route] methods.
 *
 * Usage:
 *   php console route:list
 */
#[ConsoleCommand(
    name: 'route:list',
    description: 'List all registered routes'
)]
class RouteListCommand implements CommandInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly Config $config
    ) {}

    public function execute(OutputInterface $output): int
    {
        $controllers = $this->config->getArray('controllers.list');

        if ($controllers === []) {
            $output->warning('No controllers registered in config/controllers.php');
            return 0;
        }

        $router = new Router($this->container);
        $router->registerControllers($controllers);

        $routes = $router->getRoutes();

        if ($routes === []) {
            $output->warning('No routes defined on the registered controllers.');
            return 0;
        }

        // Sort by path, then HTTP method, for a stable, scannable listing.
        usort($routes, fn(array $a, array $b): int =>
            [$a['path'], $a['method']] <=> [$b['path'], $b['method']]);

        $rows = [];
        foreach ($routes as $route) {
            $handler = $this->shortName($route['controller']) . '@' . $route['action'];
            $middleware = implode(', ', array_map([$this, 'shortName'], $route['middlewares']));
            $rows[] = [$this->colorMethod($route['method']), $route['path'], $handler, $middleware ?: '<dim>-</dim>'];
        }

        $output->table(['Method', 'URI', 'Handler', 'Middleware'], $rows);
        $output->success('Total routes: ' . count($routes));

        return 0;
    }

    private function shortName(string $class): string
    {
        $position = strrpos($class, '\\');
        return $position === false ? $class : substr($class, $position + 1);
    }

    /**
     * Color the HTTP verb: GET green, DELETE red, write methods yellow.
     */
    private function colorMethod(string $method): string
    {
        return match ($method) {
            'GET', 'HEAD' => "<info>{$method}</info>",
            'DELETE' => "<error>{$method}</error>",
            default => "<comment>{$method}</comment>",
        };
    }
}

