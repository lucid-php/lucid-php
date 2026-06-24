# Pipelines

Pipelines are deterministic workflows with explicit, ordered steps. Use pipelines for processes that need extension points.

**Events** are for "something happened".

**Pipelines** are for "this process consists of ordered steps".

## Core Concepts

### What is a Pipeline?

A pipeline is:

1. A sequence of named, ordered steps
2. Each step receives a payload and passes it to the next
3. Steps can be inserted before/after known steps
4. Used for deterministic workflows with extension points

## Pipeline vs. Events

| Aspect | Pipeline | Event |
|--------|----------|-------|
| **Semantics** | Process with ordered steps | Something happened |
| **Control** | Step-by-step processing | Fire and forget |
| **Order** | Deterministic, explicit | No guaranteed order |
| **Extension** | Insert before/after | Add listeners (unordered) |
| **Example** | Checkout workflow | Order was placed |

## Creating a Pipeline

### 1. Define Steps

Each step implements `Core\Pipeline\PipelineStepInterface`:

```php
<?php

declare(strict_types=1);

namespace App\Pipelines\Checkout;

use Core\Pipeline\PipelineNext;
use Core\Pipeline\PipelineStepInterface;

final class ValidateCartStep implements PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object
    {
        // Validate cart contents
        if (empty($payload->items)) {
            throw new EmptyCartException();
        }

        // Pass to next step
        return $next($payload);
    }
}

final class CalculateTotalsStep implements PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object
    {
        // Calculate tax, shipping, etc.
        $payload->total = $this->calculate($payload->items);

        return $next($payload);
    }

    private function calculate(array $items): float
    {
        return array_sum(array_map(fn($item) => $item->price, $items));
    }
}

final class CreateOrderStep implements PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object
    {
        // Create order in database
        $order = Order::create($payload);
        $payload->order_id = $order->id;

        return $next($payload);
    }
}
```

### 2. Define the Pipeline

Register the pipeline in your module:

```php
<?php

namespace App\Modules\Checkout;

use Core\Module\ModuleBootContext;
use Core\Pipeline\PipelineDefinition;
use App\Pipelines\Checkout\{
    ValidateCartStep,
    CalculateTotalsStep,
    CreateOrderStep,
};

public function boot(ModuleBootContext $context): void
{
    $context->pipelines->define(new PipelineDefinition(
        name: 'checkout.place_order',
        steps: [
            ValidateCartStep::class,
            CalculateTotalsStep::class,
            CreateOrderStep::class,
        ],
    ));
}
```

### 3. Create a Payload DTO

Define a typed object for your payload:

```php
<?php

declare(strict_types=1);

namespace App\Pipelines\Checkout;

final class PlaceOrderPayload
{
    public int $order_id;
    public float $total;

    public function __construct(
        public readonly array $items,
        public readonly string $customer_id,
    ) {}
}
```

### 4. Run the Pipeline

Execute the pipeline from your service:

```php
<?php

namespace App\Services;

use Core\Pipeline\PipelineRegistry;
use App\Pipelines\Checkout\PlaceOrderPayload;

final readonly class CheckoutService
{
    public function __construct(
        private PipelineRegistry $pipelines,
    ) {}

    public function placeOrder(array $items, string $customerId): int
    {
        $payload = new PlaceOrderPayload($items, $customerId);

        /** @var PlaceOrderPayload $result */
        $result = $this->pipelines
            ->get('checkout.place_order')
            ->run($payload);

        return $result->order_id;
    }
}
```

## Extending Pipelines

### Insert Before a Step

Insert a step before a known step:

```php
$context->pipelines->addBefore(
    pipeline: 'checkout.place_order',
    beforeStep: CreateOrderStep::class,
    step: FraudCheckStep::class,
);

// Result: ValidateCartStep -> CalculateTotalsStep -> FraudCheckStep -> CreateOrderStep
```

### Insert After a Step

Insert a step after a known step:

```php
$context->pipelines->addAfter(
    pipeline: 'checkout.place_order',
    afterStep: CalculateTotalsStep::class,
    step: ApplyPromotionStep::class,
);

// Result: ValidateCartStep -> CalculateTotalsStep -> ApplyPromotionStep -> CreateOrderStep
```

## Real-World Example: Checkout Pipeline

```php
<?php

declare(strict_types=1);

namespace App\Modules\Checkout;

use Core\Module\ModuleBootContext;
use Core\Module\ModuleInterface;
use Core\Pipeline\PipelineDefinition;
use App\Pipelines\Checkout\{
    ValidateCartStep,
    CalculateTotalsStep,
    ReserveInventoryStep,
    CreateOrderStep,
    DispatchOrderPlacedStep,
};

final class CheckoutModule implements ModuleInterface
{
    // ... ModuleInterface methods ...

    public function boot(ModuleBootContext $context): void
    {
        // Define the checkout pipeline
        $context->pipelines->define(new PipelineDefinition(
            name: 'checkout.place_order',
            steps: [
                ValidateCartStep::class,
                CalculateTotalsStep::class,
                ReserveInventoryStep::class,
                CreateOrderStep::class,
                DispatchOrderPlacedStep::class,
            ],
        ));

        // Payment module can extend the pipeline
        // (in its own boot method)
    }
}
```

Then, the payment module can extend it:

```php
<?php

namespace App\Modules\Payment;

public function boot(ModuleBootContext $context): void
{
    $context->pipelines->addBefore(
        pipeline: 'checkout.place_order',
        beforeStep: CreateOrderStep::class,
        step: CapturePaymentStep::class,
    );
}
```

Result:
1. ValidateCartStep
2. CalculateTotalsStep
3. ReserveInventoryStep
4. **CapturePaymentStep** (added by payment module)
5. CreateOrderStep
6. DispatchOrderPlacedStep

## Step Guidelines

### Do

- ✅ Keep steps focused and single-purpose
- ✅ Throw exceptions to stop the pipeline early
- ✅ Modify and return the payload
- ✅ Inject dependencies via constructor
- ✅ Log important operations

### Don't

- ❌ Call `$next()` multiple times
- ❌ Ignore the returned payload
- ❌ Assume next step exists (you don't know)
- ❌ Use global state
- ❌ Perform long-running I/O without timeout

## Pipeline vs. Command Pattern

Pipelines are **not** commands.

- **Pipeline**: Ordered, sequential steps for a workflow
- **Command**: Single unit of work with input/output

Use a pipeline if:
- You have multiple steps in sequence
- Steps need to be extensible
- Order matters
- Modules need to inject steps

Use a command if:
- Single piece of work
- No extension needed
- Simple input → output

Often, pipelines **call** commands:

```php
final class ValidateCartStep implements PipelineStepInterface
{
    public function __construct(
        private ValidateCartCommand $command,
    ) {}

    public function handle(object $payload, PipelineNext $next): object
    {
        $result = $this->command->execute($payload->cart);

        if (!$result->isValid) {
            throw new CartValidationException($result->errors);
        }

        return $next($payload);
    }
}
```

## Testing Pipelines

```php
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineDefinition;

$definition = new PipelineDefinition('test', [
    Step1::class,
    Step2::class,
    Step3::class,
]);

$pipeline = new Pipeline($definition->steps);

$payload = new TestPayload();
$result = $pipeline->run($payload);

$this->assertSame(3, $result->step_count);
```

Test step insertion:

```php
$registry = new PipelineRegistry();
$registry->define($definition);

$registry->addBefore('test', Step3::class, Step2Point5::class);

$pipeline = $registry->get('test');
// Verify the step was inserted at the correct position
```

## Exceptions

### PipelineNotFoundException

The pipeline you tried to access doesn't exist.

```
Pipeline "checkout.place_order" not found
```

### PipelineStepNotFoundException

You tried to insert a step before/after a step that doesn't exist.

```
Pipeline step "UnknownStep" not found
```

## Future: Conditional Steps, Parallel Execution

In the future, Lucid-PHP may support:

- Conditional steps that skip based on payload state
- Parallel step execution for independent operations
- Step composition (pipelines containing pipelines)

For now, keep logic in steps or use events for side effects.

Lucid does not cache module, pipeline, or GraphQL registry output by default. The registries are deterministic and can be cached later if profiling shows a real need.
