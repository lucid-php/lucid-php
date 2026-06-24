# Modules

Lucid-PHP uses an explicit, traceable module system. Modules are the fundamental building blocks for composing your application.

## Core Concepts

### What is a Module?

A module is a self-contained unit of application functionality that:

1. Declares its dependencies explicitly
2. Registers services into the container
3. Registers controllers, listeners, and other components during boot
4. Cannot be auto-discovered - must be explicitly registered

### Philosophy

- **No auto-discovery**: Modules are explicitly listed in configuration
- **Explicit dependencies**: Use `dependsOn()` to declare what your module needs
- **Dependency validation**: Circular and missing dependencies are caught at boot time
- **Deterministic ordering**: Modules are sorted in dependency order automatically
- **Clear lifecycle**: `register()` runs before `boot()`

## Creating a Module

A module implements `Core\Module\ModuleInterface`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Example;

use Core\Container;
use Core\Module\ModuleBootContext;
use Core\Module\ModuleInterface;

final class ExampleModule implements ModuleInterface
{
    public function name(): string
    {
        return 'app.example';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function dependsOn(): array
    {
        return []; // List module names this depends on
    }

    public function register(Container $container): void
    {
        // Register services during this phase
        $container->set(ExampleService::class, ExampleService::class);
    }

    public function boot(ModuleBootContext $context): void
    {
        // Register controllers, listeners, migrations during this phase
        $context->app->registerControllers([
            ExampleController::class,
        ]);

        $context->events->listen(
            ExampleEvent::class,
            ExampleListener::class,
        );

        $context->migrations->addPath(__DIR__ . '/../../database/migrations');
    }
}
```

## Module Registration

Register modules explicitly in a PHP configuration file:

```php
<?php

declare(strict_types=1);

// config/modules.php

use App\Modules\Example\ExampleModule;
use App\Modules\User\UserModule;

return [
    ExampleModule::class,
    UserModule::class,
];
```

Load and boot modules in your application:

```php
<?php

$modules = require 'config/modules.php';
$registry = new ModuleRegistry(array_map(fn($class) => new $class(), $modules));
$loader = new ModuleLoader(
    $registry,
    $container,
    $app,
    $events,
    $graphql,
    $pipelines,
    $migrations,
);

$loader->boot();
```

## Module Dependencies

### Declaring Dependencies

Use `dependsOn()` to declare what your module requires:

```php
public function dependsOn(): array
{
    return [
        'app.user',    // My module needs the user module
        'app.catalog', // And the catalog module
    ];
}
```

### Dependency Validation

The module system validates:

1. **No missing dependencies**: If you depend on a module that doesn't exist, boot fails
2. **No circular dependencies**: If module A depends on B which depends on A, boot fails
3. **No duplicates**: You cannot register the same module name twice

All errors include clear messages explaining what went wrong.

### Dependency Ordering

Modules are automatically sorted in dependency order. If A depends on B, then B is always booted first.

```php
// If you boot modules in this order:
[$moduleC, $moduleA, $moduleB]

// But module B depends on A, and C depends on B:
// Module B: dependsOn() = ['app.example.a']
// Module C: dependsOn() = ['app.example.b']

// They are automatically reordered to:
// [$moduleA, $moduleB, $moduleC]
```

## Module Lifecycle

### Phase 1: register()

Called first, for each module in dependency order:

- Register services into the container
- Register singleton factories
- Do not access other modules' services yet

```php
public function register(Container $container): void
{
    $container->set(MyService::class, MyService::class);
}
```

### Phase 2: boot()

Called after all `register()` calls complete, for each module in dependency order:

- Register controllers
- Register event listeners
- Register migrations
- Register GraphQL schema contributions
- Register pipeline steps
- Access and use services from the container
- Do I/O setup (connect to databases, etc.)

```php
public function boot(ModuleBootContext $context): void
{
    $service = $context->app->get(MyService::class);
    $context->events->listen(MyEvent::class, MyListener::class);
}
```

## ModuleBootContext

The boot context provides access to application registries:

```php
final class ModuleBootContext
{
    public function __construct(
        public readonly Application $app,
        public readonly EventDispatcher $events,
        public readonly GraphQLRegistry $graphql,
        public readonly PipelineRegistry $pipelines,
        public readonly MigrationPathRegistry $migrations,
    ) {}
}
```

- **$app**: Register controllers and access the container
- **$events**: Register event listeners
- **$graphql**: Register GraphQL types, queries, and mutations
- **$pipelines**: Define and configure pipelines
- **$migrations**: Register migration paths

## Best Practices

### Module Naming

Use reverse-domain notation:

```
app.user          - User module in the app
app.catalog       - Catalog module
commerce.payment  - Payment module in commerce
```

### Keep Modules Focused

Each module should have a single responsibility:

- ✅ `app.user` - User management
- ✅ `app.email` - Email sending
- ✅ `commerce.inventory` - Inventory tracking
- ❌ `app.everything` - Too broad

### Avoid Circular Dependencies

If you find yourself with circular dependencies, restructure your modules:

```
Bad:
User depends on Role
Role depends on User

Good:
Permission (new module)
User depends on Permission
Role depends on Permission
```

### Version Compatibility

Modules should declare their version. If you change the module's API, bump the version:

```php
public function version(): string
{
    return '2.0.0'; // Breaking API change from 1.0.0
}
```

## Testing Modules

Test module boot behavior:

```php
use Core\Module\ModuleRegistry;

$module = new YourModule();
$registry = new ModuleRegistry([$module]);

// Verify dependencies are validated
$sorted = $registry->getSorted();
$this->assertSame('your.module', $sorted[0]->name());
```

Test the boot context:

```php
$context = new ModuleBootContext(
    app: $app,
    events: $events,
    graphql: $graphql,
    pipelines: $pipelines,
    migrations: $migrations,
);

// Verify listeners registered, etc.
```

## Exceptions

### ModuleNotFoundException

A module depends on another module that doesn't exist.

```
Module "app.user" depends on missing module "app.permission"
```

### ModuleAlreadyRegisteredException

You tried to register a module with a name that's already been used.

```
Module "app.user" is already registered
```

### CircularModuleDependencyException

Modules have circular dependencies.

```
Circular dependency detected involving module "app.user"
```

## Future: Application vs. Framework Modules

Currently, all modules are in `App\Modules`. Framework modules will eventually be in `Core\Modules` with different conventions, but the same explicit interface.

Lucid does not cache module, pipeline, or GraphQL registry output by default. The registries are deterministic and can be cached later if profiling shows a real need.
