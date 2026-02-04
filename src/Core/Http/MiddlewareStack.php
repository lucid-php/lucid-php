<?php

declare(strict_types=1);

namespace Core\Http;

use Closure;

class MiddlewareStack implements RequestHandlerInterface
{
    private array $middlewares;
    private Closure $coreAction;
    private int $index = 0;

    /**
     * @param MiddlewareInterface[] $middlewares
     * @param Closure(Request): Response $coreAction
     */
    public function __construct(array $middlewares, Closure $coreAction)
    {
        $this->middlewares = $middlewares;
        $this->coreAction = $coreAction;
    }

    public function handle(Request $request): Response
    {
        if (!isset($this->middlewares[$this->index])) {
            return ($this->coreAction)($request);
        }

        $middleware = $this->middlewares[$this->index];
        
        // Advance the pointer for the *next* call to handle()
        // We clone the stack so each 'next' handler has the correct index state
        // This is a simple recursive simulation without nested objects.
        $next = clone $this;
        $next->index++;

        return $middleware->process($request, $next);
    }
}
