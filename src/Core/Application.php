<?php

declare(strict_types=1);

namespace Core;

use Core\Http\Request;

class Application
{
    private Container $container;
    private Router $router;

    public function __construct()
    {
        $this->container = new Container();
        $this->router = new Router($this->container);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function addGlobalMiddleware(string $middlewareClass): self
    {
        $this->router->addGlobalMiddleware($middlewareClass);
        return $this;
    }

    public function registerControllers(array $controllers): self
    {
        $this->router->registerControllers($controllers);
        return $this;
    }

    public function run(): void
    {
        $request = Request::createFromGlobals();
        $response = $this->router->dispatch($request);
        $response->send();
    }
}
