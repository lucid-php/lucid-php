<?php

declare(strict_types=1);

namespace Core;

use Core\Attribute\Middleware;
use Core\Attribute\Route;
use Core\Attribute\RoutePrefix;
use Core\Attribute\QueryParam;
use Core\Http\ApiResponse;
use Core\Http\HttpException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\MiddlewareInterface;
use Core\Http\MiddlewareStack;
use Core\Http\ValidatedDTO;
use Core\Validation\ValidationException;
use Core\Validation\Validator;
use ReflectionClass;
use ReflectionNamedType;
use Uri\Rfc3986\Uri;

class Router
{
    private array $routes = [];
    private array $globalMiddlewares = [];
    private Validator $validator;

    public function __construct(private final Container $container) {
        $this->validator = new Validator();
    }

    public function addGlobalMiddleware(string $middlewareClass): void
    {
        $this->globalMiddlewares[] = $middlewareClass;
    }

    public function registerControllers(array $controllers): void
    {
        foreach ($controllers as $controller) {
            $reflection = new ReflectionClass($controller);

            $prefix = '';
            $prefixAttributes = $reflection->getAttributes(RoutePrefix::class);
            if (!empty($prefixAttributes)) {
                $prefix = $prefixAttributes[0]->newInstance()->prefix;
            }

            // Class Level Middleware
            $classMiddlewares = [];
            foreach ($reflection->getAttributes(Middleware::class) as $attr) {
                $classMiddlewares[] = $attr->newInstance()->middlewareClass;
            }

            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class);
                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();
                    // Method Level Middleware
                    $methodMiddlewares = [];
                    foreach ($method->getAttributes(Middleware::class) as $attr) {
                        $methodMiddlewares[] = $attr->newInstance()->middlewareClass;
                    }

                    $fullPath = rtrim($prefix . $route->path, '/');
                    if ($fullPath === '') {
                        $fullPath = '/';
                    }

                    // Store both the original pattern and the regex pattern
                    $pattern = $this->convertToRegex($fullPath);
                    
                    $this->routes[$route->method][] = [
                        'path' => $fullPath,
                        'pattern' => $pattern,
                        'controller' => $controller,
                        'method' => $method->getName(),
                        'middlewares' => [...$this->globalMiddlewares, ...$classMiddlewares, ...$methodMiddlewares]
                    ];
                }
            }
        }
    }

    public function dispatch(Request $request): Response
    {
        // PHP 8.5: Use native URI extension for RFC 3986 compliant parsing
        $uri = (new Uri($request->uri))->getPath();
        $routesForMethod = $this->routes[$request->method] ?? [];
        
        $handlerConfig = null;
        $params = [];

        // Try to match the URI against registered routes
        foreach ($routesForMethod as $route) {
            if ($route['path'] === $uri) {
                // Exact match (no parameters)
                $handlerConfig = $route;
                break;
            }
            
            // Try pattern match (with parameters)
            if (preg_match($route['pattern'], $uri, $matches)) {
                $handlerConfig = $route;
                // Extract named parameters
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = $value;
                    }
                }
                break;
            }
        }

        if (!$handlerConfig) {
            return new Response("404 Not Found", 404);
        }

        $controllerClass = $handlerConfig['controller'];
        $methodName = $handlerConfig['method'];
        $middlewareClasses = $handlerConfig['middlewares'];

        // Add controller and method info to request for middleware access
        // (e.g., RateLimitMiddleware needs this to read #[RateLimit] attribute)
        $request = $request
            ->withAttribute('_controller', $controllerClass)
            ->withAttribute('_method', $methodName)
            ->withAttribute('_route_params', $params);

        // Instantiate middlewares
        $middlewares = array_map(
            fn($class) => $this->container->get($class),
            $middlewareClasses
        );

        // Define the Core Action (The Controller Method)
        // Exception handling is now done by ExceptionMiddleware - no try/catch here
        $coreAction = function (Request $req) use ($controllerClass, $methodName) {
            $controllerInstance = $this->container->get($controllerClass);
            
            $args = $this->resolveMethodDependencies($controllerClass, $methodName, $req);
            $response = $controllerInstance->$methodName(...$args);

            if ($response instanceof Response) {
                return $response;
            } elseif ($response instanceof ApiResponse) {
                return $response->toResponse();
            } elseif (is_array($response)) {
                return Response::json($response);
            } else {
                return Response::text((string) $response);
            }
        };

        // Create the Stack and Execute
        // Exceptions will be caught by ExceptionMiddleware if registered
        $stack = new MiddlewareStack($middlewares, $coreAction);
        
        return $stack->handle($request);
    }
    
    private function resolveMethodDependencies(string $controller, string $method, Request $request): array
    {
        $reflection = new ReflectionClass($controller);
        $methodRef = $reflection->getMethod($method);
        $args = [];
        $routeParams = $request->getAttribute('_route_params', []);

        foreach ($methodRef->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $type = $parameter->getType();
            
            // Check if parameter has #[QueryParam] attribute
            $queryParamAttrs = $parameter->getAttributes(QueryParam::class);
            $isQueryParam = !empty($queryParamAttrs);
            
            // 1. Query parameters (marked with #[QueryParam] attribute)
            if ($isQueryParam) {
                $value = $request->getQueryParam($paramName);
                
                // Use default value if not provided
                if ($value === null && $parameter->isDefaultValueAvailable()) {
                    $value = $parameter->getDefaultValue();
                }
                
                // Cast to appropriate type if type hint exists
                if ($value !== null && $type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();
                    if ($typeName === 'int') {
                        $value = (int) $value;
                    } elseif ($typeName === 'bool') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($typeName === 'float') {
                        $value = (float) $value;
                    } elseif ($typeName === 'string') {
                        $value = (string) $value;
                    }
                }
                
                $args[] = $value;
                continue;
            }
            
            // 2. Route parameters (string or int types from URI)
            if (array_key_exists($paramName, $routeParams)) {
                $value = $routeParams[$paramName];
                
                // Cast to appropriate type if type hint exists
                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();
                    if ($typeName === 'int') {
                        $value = (int) $value;
                    } elseif ($typeName === 'string') {
                        $value = (string) $value;
                    }
                }
                
                $args[] = $value;
                continue;
            }
            
            if (!$type instanceof ReflectionNamedType) {
                 continue;
            }
            $typeName = $type->getName();

            // 3. Inject Request
            if ($typeName === Request::class) {
                $args[] = $request;
                continue;
            }

            // 4. Inject DTOs (Marked by Interface)
            if (interface_exists(ValidatedDTO::class) && is_subclass_of($typeName, ValidatedDTO::class)) {
                $args[] = $this->validator->validateAndHydrate($typeName, $request->body);
                continue;
            }
            
            // 5. Use default value if parameter has one
            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }
            
            throw new \Exception("Cannot resolve parameter [{$parameter->getName()}] in [$controller::$method].");
        }

        return $args;
    }
    
    /**
     * Convert route path with {param} placeholders to regex pattern
     * Example: /users/{id}/posts/{slug} becomes /^\/users\/(?<id>[^\/]+)\/posts\/(?<slug>[^\/]+)$/
     */
    private function convertToRegex(string $path): string
    {
        // Escape forward slashes and special regex characters
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}
