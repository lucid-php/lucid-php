<?php

declare(strict_types=1);

namespace Core;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

/**
 * Dependency Injection Container
 *
 * The get() method is marked with #[\NoDiscard] to ensure the resolved
 * instance is used. This prevents accidentally resolving dependencies
 * without capturing the result.
 */
class Container
{
    private array $instances = [];

    /** @var array<string, class-string> Interface/abstract => concrete class */
    private array $bindings = [];

    public function set(string $class, object $instance): void
    {
        $this->instances[$class] = $instance;
    }

    /**
     * Bind an interface (or abstract class) to a concrete implementation, so
     * autowiring can resolve a dependency type-hinted on the interface.
     *
     * Explicit: you declare the mapping in bootstrap; nothing is auto-discovered.
     *
     * @param class-string $abstract The interface or abstract class name
     * @param class-string $concrete The concrete class to instantiate for it
     */
    public function bind(string $abstract, string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function has(string $class): bool
    {
        return isset($this->instances[$class]) || isset($this->bindings[$class]);
    }

    #[\NoDiscard]
    public function get(string $class): object
    {
        if (isset($this->instances[$class])) {
            $instance = $this->instances[$class];

            // If instance is a closure, execute it
            if ($instance instanceof \Closure) {
                $instance = $instance($this);
                $this->instances[$class] = $instance; // Cache the result
            }

            return $instance;
        }

        // Resolve interface/abstract bindings to their concrete implementation.
        if (isset($this->bindings[$class])) {
            return $this->get($this->bindings[$class]);
        }

        return $this->resolve($class);
    }

    private function resolve(string $class): object
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new \Exception("Target class [$class] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class [$class] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new \Exception("Cannot resolve primitive dependency [{$parameter->getName()}] in class [$class]. Explicitly configure it.");
            }

            $dependencies[] = $this->get($type->getName());
        }

        // Simplicity: we don't cache resolved instances unless explicitly requested,
        // effectively making them transient by default.
        // To make them singletons, one would add to $this->instances here.
        return $reflector->newInstanceArgs($dependencies);
    }
}
