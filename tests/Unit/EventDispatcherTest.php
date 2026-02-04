<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Event\UserCreated;
use App\Event\UserDeleted;
use App\Listener\SendWelcomeEmail;
use App\Listener\LogUserCreation;
use App\Listener\CleanupUserData;
use Core\Container;
use Core\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    private Container $container;
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->dispatcher = new EventDispatcher($this->container);
    }

    public function test_can_register_single_listener(): void
    {
        $this->dispatcher->listen(UserCreated::class, SendWelcomeEmail::class);

        $listeners = $this->dispatcher->getListeners(UserCreated::class);

        $this->assertCount(1, $listeners);
        $this->assertEquals(SendWelcomeEmail::class, $listeners[0]);
    }

    public function test_can_register_multiple_listeners(): void
    {
        $this->dispatcher->listen(UserCreated::class, [
            SendWelcomeEmail::class,
            LogUserCreation::class,
        ]);

        $listeners = $this->dispatcher->getListeners(UserCreated::class);

        $this->assertCount(2, $listeners);
        $this->assertEquals(SendWelcomeEmail::class, $listeners[0]);
        $this->assertEquals(LogUserCreation::class, $listeners[1]);
    }

    public function test_can_dispatch_event_to_listeners(): void
    {
        $this->dispatcher->listen(UserCreated::class, LogUserCreation::class);
        
        $event = new UserCreated(
            userId: 123,
            name: 'John Doe',
            email: 'john@example.com'
        );

        // Should not throw exception
        $this->dispatcher->dispatch($event);
        
        $this->assertTrue(true);
    }

    public function test_dispatch_does_nothing_when_no_listeners(): void
    {
        $event = new UserCreated(
            userId: 123,
            name: 'John Doe',
            email: 'john@example.com'
        );

        // Should not throw exception
        $this->dispatcher->dispatch($event);
        
        $this->assertTrue(true);
    }

    public function test_can_check_if_event_has_listeners(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners(UserCreated::class));
        
        $this->dispatcher->listen(UserCreated::class, SendWelcomeEmail::class);
        
        $this->assertTrue($this->dispatcher->hasListeners(UserCreated::class));
    }

    public function test_can_forget_listeners(): void
    {
        $this->dispatcher->listen(UserCreated::class, SendWelcomeEmail::class);
        $this->assertTrue($this->dispatcher->hasListeners(UserCreated::class));
        
        $this->dispatcher->forget(UserCreated::class);
        
        $this->assertFalse($this->dispatcher->hasListeners(UserCreated::class));
    }

    public function test_different_events_have_separate_listeners(): void
    {
        $this->dispatcher->listen(UserCreated::class, SendWelcomeEmail::class);
        $this->dispatcher->listen(UserDeleted::class, CleanupUserData::class);

        $createdListeners = $this->dispatcher->getListeners(UserCreated::class);
        $deletedListeners = $this->dispatcher->getListeners(UserDeleted::class);

        $this->assertCount(1, $createdListeners);
        $this->assertCount(1, $deletedListeners);
        $this->assertEquals(SendWelcomeEmail::class, $createdListeners[0]);
        $this->assertEquals(CleanupUserData::class, $deletedListeners[0]);
    }

    public function test_returns_empty_array_for_unregistered_event(): void
    {
        $listeners = $this->dispatcher->getListeners(UserCreated::class);
        
        $this->assertIsArray($listeners);
        $this->assertEmpty($listeners);
    }
}
