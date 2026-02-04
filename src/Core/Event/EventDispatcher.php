<?php

declare(strict_types=1);

namespace Core\Event;

use Core\Container;

/**
 * Explicit Event Dispatcher
 * 
 * Philosophy:
 * - No magic event discovery
 * - Explicit listener registration
 * - Events are typed classes (Command+Click works)
 * - Listeners resolved from container (dependency injection)
 * - No global event() helper
 */
class EventDispatcher
{
    /** @var array<string, array<string>> */
    private array $listeners = [];

    public function __construct(
        private final Container $container
    ) {}

    /**
     * Register one or more listeners for an event
     * 
     * @param string $eventClass Fully qualified event class name
     * @param string|array<string> $listenerClasses Listener class name(s)
     */
    public function listen(string $eventClass, string|array $listenerClasses): void
    {
        $listeners = is_array($listenerClasses) ? $listenerClasses : [$listenerClasses];
        
        foreach ($listeners as $listener) {
            $this->listeners[$eventClass][] = $listener;
        }
    }

    /**
     * Dispatch an event to all registered listeners
     * 
     * @param object $event The event instance to dispatch
     */
    public function dispatch(object $event): void
    {
        $eventClass = $event::class;
        
        if (!isset($this->listeners[$eventClass])) {
            return;
        }

        foreach ($this->listeners[$eventClass] as $listenerClass) {
            $listener = $this->container->get($listenerClass);
            
            // Call handle() method on listener
            $listener->handle($event);
        }
    }

    /**
     * Get all registered listeners for an event
     * 
     * @param string $eventClass
     * @return array<string>
     */
    public function getListeners(string $eventClass): array
    {
        return $this->listeners[$eventClass] ?? [];
    }

    /**
     * Remove all listeners for an event
     */
    public function forget(string $eventClass): void
    {
        unset($this->listeners[$eventClass]);
    }

    /**
     * Check if event has any listeners
     */
    public function hasListeners(string $eventClass): bool
    {
        return isset($this->listeners[$eventClass]) && count($this->listeners[$eventClass]) > 0;
    }
}
