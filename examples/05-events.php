<?php

declare(strict_types=1);

/**
 * Example 5: Event System
 * 
 * Demonstrates:
 * - Creating events
 * - Creating listeners
 * - Dispatching events
 * - Multiple listeners for same event
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Event\EventDispatcher;

// ===========================
// Event Classes
// ===========================

class UserRegisteredEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $name,
        public readonly \DateTimeImmutable $registeredAt
    ) {}
}

class OrderPlacedEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $userId,
        public readonly float $total,
        public readonly array $items
    ) {}
}

class PaymentProcessedEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $paymentMethod,
        public readonly float $amount,
        public readonly bool $success
    ) {}
}

// ===========================
// Listener Classes
// ===========================

class SendWelcomeEmailListener
{
    public function handle(UserRegisteredEvent $event): void
    {
        echo "[Email] Sending welcome email to {$event->email}\n";
        echo "        Subject: Welcome {$event->name}!\n";
        echo "        Content: Thank you for registering...\n\n";
    }
}

class CreateUserProfileListener
{
    public function handle(UserRegisteredEvent $event): void
    {
        echo "[Database] Creating profile for user #{$event->userId}\n";
        echo "           Name: {$event->name}\n";
        echo "           Email: {$event->email}\n\n";
    }
}

class LogUserRegistrationListener
{
    public function handle(UserRegisteredEvent $event): void
    {
        $date = $event->registeredAt->format('Y-m-d H:i:s');
        echo "[Logger] User registered: #{$event->userId} at {$date}\n\n";
    }
}

class SendOrderConfirmationListener
{
    public function handle(OrderPlacedEvent $event): void
    {
        echo "[Email] Sending order confirmation for order #{$event->orderId}\n";
        echo "        Total: \${$event->total}\n";
        echo "        Items: " . count($event->items) . "\n\n";
    }
}

class UpdateInventoryListener
{
    public function handle(OrderPlacedEvent $event): void
    {
        echo "[Inventory] Updating stock for order #{$event->orderId}\n";
        foreach ($event->items as $item) {
            echo "            Product #{$item['product_id']}: -{$item['quantity']}\n";
        }
        echo "\n";
    }
}

class ProcessPaymentListener
{
    public function handle(OrderPlacedEvent $event): void
    {
        echo "[Payment] Processing payment for order #{$event->orderId}\n";
        echo "          Amount: \${$event->total}\n\n";
    }
}

class SendPaymentNotificationListener
{
    public function handle(PaymentProcessedEvent $event): void
    {
        $status = $event->success ? 'successful' : 'failed';
        echo "[Notification] Payment {$status} for order #{$event->orderId}\n";
        echo "               Amount: \${$event->amount}\n";
        echo "               Method: {$event->paymentMethod}\n\n";
    }
}

// ===========================
// Example Usage
// ===========================

echo "Event System Examples:\n";
echo "=====================\n\n";

// Create container and dispatcher
use Core\Container;

$container = new Container();
$dispatcher = new EventDispatcher($container);

// Register listeners
$dispatcher->listen(UserRegisteredEvent::class, SendWelcomeEmailListener::class);
$dispatcher->listen(UserRegisteredEvent::class, CreateUserProfileListener::class);
$dispatcher->listen(UserRegisteredEvent::class, LogUserRegistrationListener::class);

$dispatcher->listen(OrderPlacedEvent::class, SendOrderConfirmationListener::class);
$dispatcher->listen(OrderPlacedEvent::class, UpdateInventoryListener::class);
$dispatcher->listen(OrderPlacedEvent::class, ProcessPaymentListener::class);

$dispatcher->listen(PaymentProcessedEvent::class, SendPaymentNotificationListener::class);

echo "=== Example 1: User Registration ===\n\n";

$userEvent = new UserRegisteredEvent(
    userId: 123,
    email: 'john@example.com',
    name: 'John Doe',
    registeredAt: new \DateTimeImmutable()
);

$dispatcher->dispatch($userEvent);

echo "=== Example 2: Order Placed ===\n\n";

$orderEvent = new OrderPlacedEvent(
    orderId: 456,
    userId: 123,
    total: 149.99,
    items: [
        ['product_id' => 1, 'quantity' => 2, 'price' => 49.99],
        ['product_id' => 3, 'quantity' => 1, 'price' => 50.01],
    ]
);

$dispatcher->dispatch($orderEvent);

echo "=== Example 3: Payment Processed ===\n\n";

$paymentEvent = new PaymentProcessedEvent(
    orderId: 456,
    paymentMethod: 'credit_card',
    amount: 149.99,
    success: true
);

$dispatcher->dispatch($paymentEvent);

echo "\n=== Key Benefits ===\n\n";
echo "1. Decoupling: Controllers don't need to know about email, logging, etc.\n";
echo "2. Multiple listeners: One event can trigger many actions\n";
echo "3. Easy to extend: Add new listeners without changing existing code\n";
echo "4. Testable: Each listener can be tested independently\n";
echo "5. Explicit: All events and listeners are registered in config\n\n";

echo "Configuration (config/events.php):\n";
echo "\$dispatcher->listen(UserRegisteredEvent::class, SendWelcomeEmailListener::class);\n";
echo "\$dispatcher->listen(UserRegisteredEvent::class, CreateUserProfileListener::class);\n";
