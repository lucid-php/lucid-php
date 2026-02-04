# Event System

The framework provides an **explicit event dispatcher** for decoupling application logic. Events are strongly-typed classes, listeners are explicitly registered, and there's no magic auto-discovery.

## Philosophy

- **No Magic:** All listeners are explicitly registered—no scanning directories or reflection
- **Typed Events:** Events are readonly classes with typed properties (Command+Click works)
- **Explicit Dispatch:** You call `$events->dispatch(new Event(...))` where you want it
- **Dependency Injection:** Listeners are resolved from container—can inject services
- **Zero Hidden Behavior:** No global event() helper, no silent failures

## Core Concepts

**Events are Data Containers:**
- Readonly classes that carry information
- No behavior, just typed properties
- Immutable after creation

**Listeners are Handlers:**
- Classes with a `handle(EventClass $event)` method
- Resolved from container (can inject dependencies)
- Executed in registration order

**Dispatcher is the Mediator:**
- Holds event → listener mappings
- Dispatches events to registered listeners
- Resolves listeners from container

## Setup

### 1. Create Event Classes

```php
// src/App/Event/UserCreated.php
namespace App\Event;

readonly class UserCreated
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
    ) {}
}
```

### 2. Create Listener Classes

```php
// src/App/Listener/SendWelcomeEmail.php
namespace App\Listener;

use App\Event\UserCreated;

class SendWelcomeEmail
{
    public function __construct(
        private readonly Mailer $mailer  // Injected from container
    ) {}

    public function handle(UserCreated $event): void
    {
        $this->mailer->send(
            to: $event->email,
            subject: 'Welcome!',
            body: "Hello {$event->name}, welcome to our platform!"
        );
    }
}
```

### 3. Register Listeners in `public/index.php`

```php
use Core\Event\EventDispatcher;
use App\Event\UserCreated;
use App\Event\UserDeleted;
use App\Listener\SendWelcomeEmail;
use App\Listener\LogUserCreation;
use App\Listener\CleanupUserData;

// Create dispatcher
$events = new EventDispatcher($app->getContainer());

// Explicitly register listeners (no magic)
$events->listen(UserCreated::class, [
    SendWelcomeEmail::class,
    LogUserCreation::class,
]);

$events->listen(UserDeleted::class, [
    CleanupUserData::class,
]);

// Register in container
$app->getContainer()->set(EventDispatcher::class, $events);
```

### 4. Dispatch Events in Controllers

```php
use App\Event\UserCreated;
use Core\Event\EventDispatcher;

class ApiController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EventDispatcher $events
    ) {}

    #[Route('POST', '/users')]
    public function createUser(CreateUserDTO $data): Response
    {
        $user = $this->users->create(
            $data->name,
            $data->email,
            $data->password
        );

        // Explicit event dispatch
        $this->events->dispatch(new UserCreated(
            userId: $user->id,
            name: $user->name,
            email: $user->email
        ));

        return Response::json(['id' => $user->id], 201);
    }
}
```

## Usage Examples

### Multiple Listeners per Event

```php
$events->listen(OrderCreated::class, [
    SendOrderConfirmation::class,
    UpdateInventory::class,
    NotifyWarehouse::class,
    LogOrderCreation::class,
]);

// All 4 listeners execute when event is dispatched
$events->dispatch(new OrderCreated($orderId));
```

### Listener with Dependencies

```php
class UpdateInventory
{
    public function __construct(
        private readonly InventoryRepository $inventory,
        private readonly Logger $logger
    ) {}

    public function handle(OrderCreated $event): void
    {
        foreach ($event->items as $item) {
            $this->inventory->decrementStock($item->productId, $item->quantity);
        }
        
        $this->logger->info('Inventory updated', [
            'order_id' => $event->orderId
        ]);
    }
}
```

### Checking for Listeners

```php
if ($events->hasListeners(UserCreated::class)) {
    // Only dispatch if someone is listening
    $events->dispatch(new UserCreated(...));
}

// Get all registered listeners
$listeners = $events->getListeners(UserCreated::class);
// Returns: [SendWelcomeEmail::class, LogUserCreation::class]
```

### Removing Listeners (Testing)

```php
// In tests, you might want to disable certain listeners
$events->forget(UserCreated::class);
```

## API Reference

### EventDispatcher Methods

```php
// Register single listener
$dispatcher->listen(EventClass::class, ListenerClass::class);

// Register multiple listeners
$dispatcher->listen(EventClass::class, [
    Listener1::class,
    Listener2::class,
]);

// Dispatch event to all listeners
$dispatcher->dispatch(new EventClass(...));

// Check if event has listeners
$hasListeners = $dispatcher->hasListeners(EventClass::class);

// Get all listeners for event
$listeners = $dispatcher->getListeners(EventClass::class);

// Remove all listeners for event
$dispatcher->forget(EventClass::class);
```

## Best Practices

### ✅ DO:
- Make events readonly classes with typed properties
- Name events in past tense: `UserCreated`, `OrderPlaced`, `PaymentReceived`
- Keep events simple—just data, no behavior
- Register listeners explicitly in bootstrap file
- Use dependency injection in listeners
- Keep listeners focused—one responsibility per listener

### ❌ DON'T:
- Use magic methods like `__invoke()` (explicit `handle()` is better)
- Put business logic in events (they're just data)
- Dispatch events inside listeners (causes complexity)
- Use string event names (use typed classes)
- Auto-discover listeners by directory scanning
- Modify event properties (they're readonly)

## Philosophy Compliance

✅ **Zero Magic:** All listeners explicitly registered—no auto-discovery  
✅ **Strict Typing:** Events are typed classes, not arrays or strings  
✅ **Explicit:** You call `dispatch()` exactly where you want events fired  
✅ **Attributes-First:** N/A—events are code-driven, not config-driven  
✅ **Modern PHP:** Uses readonly classes, constructor property promotion  
✅ **Traceable:** Command+Click on event class takes you to definition
