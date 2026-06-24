# GraphQL

Lucid-PHP provides explicit GraphQL support through the `webonyx/graphql-php` library. GraphQL is an adapter layer for your domain, not part of your business logic.

## Core Concepts

### Separation of Concerns

- **Domain**: Services, repositories, business logic (no GraphQL)
- **GraphQL**: Query/mutation resolvers, type definitions
- **Transport**: HTTP endpoint that executes GraphQL

GraphQL should **never** contain business logic. It's purely a data layer.

## Architecture

```
HTTP Request (POST /graphql)
       ↓
GraphQL Endpoint
       ↓
Query Parser & Validator
       ↓
Resolver (calls application service)
       ↓
Service (contains business logic)
       ↓
Repository (data access)
       ↓
GraphQL Response (JSON)
```

## Creating GraphQL Types

### Type Definitions

Use `webonyx/graphql-php` to define types:

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class UserType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'User',
            'fields' => [
                'id' => [
                    'type' => Type::string(),
                    'description' => 'The user ID',
                ],
                'email' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The user email',
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The user name',
                ],
                'createdAt' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'When the user was created',
                ],
            ],
            'description' => 'A user in the system',
        ]);
    }
}
```

### Registering Types

Register types in your module's boot phase:

```php
<?php

namespace App\Modules\User;

use Core\Module\ModuleBootContext;
use App\GraphQL\Types\UserType;

public function boot(ModuleBootContext $context): void
{
    $context->graphql->registerType('User', new UserType());
}
```

## Creating Queries

### Query Resolver

Resolvers are simple functions that call application services:

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers\User;

use App\Services\User\UserServiceInterface;
use App\GraphQL\Types\UserType;
use GraphQL\Type\Definition\Type;

final class GetUserResolver
{
    public function __construct(
        private UserServiceInterface $userService,
    ) {}

    public function __invoke(): array
    {
        return [
            'type' => Type::nonNull(new UserType()),
            'args' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The user ID',
                ],
            ],
            'resolve' => function ($root, array $args) {
                $user = $this->userService->findById($args['id']);

                if (!$user) {
                    throw new \Exception('User not found');
                }

                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'createdAt' => $user->created_at,
                ];
            },
        ];
    }
}
```

### Register Query

Register the query in your module (explicit class registration, no helper functions):

```php
<?php

namespace App\Modules\User;

use Core\GraphQL\GraphQLQuery;
use Core\Module\ModuleBootContext;
use App\GraphQL\Resolvers\User\GetUserResolver;
use GraphQL\Type\Definition\Type;

public function boot(ModuleBootContext $context): void
{
    $context->graphql->registerQuery(new GraphQLQuery(
        name: 'user',
        type: Type::nonNull(Type::string()),
        args: [
            'id' => ['type' => Type::nonNull(Type::string())],
        ],
        resolver: GetUserResolver::class,
    ));
}
```

## Creating Mutations

### Mutation Resolver

Mutations follow the same pattern as queries:

```php
<?php

namespace App\GraphQL\Resolvers\User;

use App\Services\User\UserServiceInterface;
use App\Services\User\CreateUserCommand;
use App\GraphQL\Types\UserType;
use GraphQL\Type\Definition\Type;

final class CreateUserResolver
{
    public function __construct(
        private UserServiceInterface $userService,
    ) {}

    public function __invoke(): array
    {
        return [
            'type' => Type::nonNull(new UserType()),
            'args' => [
                'email' => [
                    'type' => Type::nonNull(Type::string()),
                ],
                'password' => [
                    'type' => Type::nonNull(Type::string()),
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                ],
            ],
            'resolve' => function ($root, array $args) {
                $command = new CreateUserCommand(
                    email: $args['email'],
                    password: $args['password'],
                    name: $args['name'],
                );

                $result = $this->userService->create($command);

                return [
                    'id' => $result->id,
                    'email' => $result->email,
                    'name' => $result->name,
                    'createdAt' => now()->toIso8601String(),
                ];
            },
        ];
    }
}
```

### Register Mutation

```php
<?php

namespace App\Modules\User;

use Core\GraphQL\GraphQLMutation;
use App\GraphQL\Resolvers\User\CreateUserResolver;
use GraphQL\Type\Definition\Type;

public function boot(ModuleBootContext $context): void
{
    $context->graphql->registerMutation(new GraphQLMutation(
        name: 'createUser',
        type: Type::nonNull(Type::string()),
        args: [
            'email' => ['type' => Type::nonNull(Type::string())],
            'password' => ['type' => Type::nonNull(Type::string())],
            'name' => ['type' => Type::nonNull(Type::string())],
        ],
        resolver: CreateUserResolver::class,
    ));
}
```

## GraphQL Endpoint

Add the GraphQL endpoint to your routes:

```php
<?php

// routes/api.php
use Core\Router;
use App\Http\Controllers\GraphQL\GraphQLController;

return function (Router $router) {
    $router->post('/graphql', [GraphQLController::class, 'execute']);
};
```

### Endpoint Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\GraphQL;

use Core\GraphQL\GraphQLRegistry;
use Core\Http\Request;
use Core\Http\Response;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Error\FormattedError;

final readonly class GraphQLController
{
    public function __construct(
        private GraphQLRegistry $graphql,
    ) {}

    public function execute(Request $request): Response
    {
        try {
            $data = json_decode($request->body(), true) ?? [];

            $query = $data['query'] ?? null;
            if (!$query) {
                return $this->errorResponse('No query provided', 400);
            }

            $variables = $data['variables'] ?? null;
            $operationName = $data['operationName'] ?? null;

            // Build schema from registry
            $schema = new Schema([
                'query' => $this->buildQueryType(),
                'mutation' => $this->buildMutationType(),
            ]);

            // Execute
            $result = GraphQL::executeSync(
                $schema,
                $query,
                null,
                null,
                $variables,
                $operationName,
            );

            return new Response(
                json_encode([
                    'data' => $result->data,
                    'errors' => $result->errors ? array_map(
                        fn($e) => FormattedError::createFromException($e),
                        $result->errors
                    ) : null,
                ]),
                200,
                ['Content-Type' => 'application/json']
            );
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    private function buildQueryType()
    {
        return new ObjectType([
            'name' => 'Query',
            'fields' => $this->graphql->getQueries(),
        ]);
    }

    private function buildMutationType()
    {
        $mutations = $this->graphql->getMutations();

        return $mutations ? new ObjectType([
            'name' => 'Mutation',
            'fields' => $mutations,
        ]) : null;
    }

    private function errorResponse(string $message, int $status): Response
    {
        return new Response(
            json_encode([
                'errors' => [['message' => $message]],
            ]),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}
```

## Example Query

```graphql
query GetUser {
  user(id: "123") {
    id
    email
    name
    createdAt
  }
}
```

Response:

```json
{
  "data": {
    "user": {
      "id": "123",
      "email": "user@example.com",
      "name": "User Name",
      "createdAt": "2026-06-23T12:00:00Z"
    }
  }
}
```

## Example Mutation

```graphql
mutation CreateUser {
  createUser(
    email: "new@example.com"
    password: "secret"
    name: "New User"
  ) {
    id
    email
    name
    createdAt
  }
}
```

Response:

```json
{
  "data": {
    "createUser": {
      "id": "456",
      "email": "new@example.com",
      "name": "New User",
      "createdAt": "2026-06-23T12:05:00Z"
    }
  }
}
```

## Best Practices

### 1. Resolvers Call Services

```php
// Good: Resolver calls service
public function resolve($root, $args)
{
    return $this->userService->findById($args['id']);
}

// Bad: Business logic in resolver
public function resolve($root, $args)
{
    return User::find($args['id']);
}
```

### 2. Use Input Types

```php
// Good: Structured input
public function __invoke(): array
{
    return [
        'type' => ...,
        'args' => [
            'input' => [
                'type' => Type::nonNull(new CreateUserInputType()),
            ],
        ],
        'resolve' => fn($root, $args) => $this->service->create($args['input']),
    ];
}
```

### 3. Validate at Edges

```php
// Good: Validate in resolver/controller
if (!$this->validator->validate($args)) {
    throw new \GraphQL\Error\UserError('Invalid input');
}

// Bad: Validation in service
// Service should assume input is valid
```

### 4. Map to Domain Objects

```php
// Good: Map GraphQL response to domain
$user = $this->userService->findById($args['id']);
return [
    'id' => $user->id,
    'email' => $user->email,
    'name' => $user->name,
];

// Bad: Return domain object directly
return $user;
```

## Testing GraphQL

```php
<?php

namespace Tests\GraphQL;

use PHPUnit\Framework\TestCase;
use Core\GraphQL\GraphQLRegistry;
use GraphQL\Type\Schema;
use GraphQL\GraphQL;

final class UserQueryTest extends TestCase
{
    public function testGetUserQuery(): void
    {
        $registry = new GraphQLRegistry();
        // Register test types...

        $query = '
            query {
                user(id: "123") {
                    id
                    email
                }
            }
        ';

        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => $registry->getQueries(),
            ]),
        ]);

        $result = GraphQL::executeSync($schema, $query);

        $this->assertNull($result->errors);
        $this->assertSame('123', $result->data['user']['id']);
    }
}
```

## Future: Subscriptions, Federation

Currently, Lucid-PHP supports queries and mutations. Future versions may add:

- Subscriptions (real-time updates)
- Apollo Federation (distributed schemas)
- DataLoader (N+1 query prevention)

## Explicitness Rules (Non-Negotiable)

- No resolver scanning.
- No type scanning.
- No annotation-based schema generation.
- No ORM-backed model exposure.
- No facades.
- No global helpers.

Lucid GraphQL does not auto-discover types, queries, mutations, or resolvers. Every GraphQL contribution must be registered explicitly in PHP.

Lucid does not cache module, pipeline, or GraphQL registry output by default. The registries are deterministic and can be cached later if profiling shows a real need.
