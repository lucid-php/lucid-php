# Framework Philosophy

This document defines the core principles guiding Lucid-PHP. These are not suggestions—they are **architectural constraints** that must be preserved.

---

## Core Principles

### 1. Zero Magic

**Definition**: Every dependency, every route, every piece of configuration is explicitly declared and traceable.

**Rules**:
- ❌ No facades (`Route::get()`, `DB::table()`)
- ❌ No global helpers (`app()`, `config()`, `auth()`)
- ❌ No service auto-discovery by directory scanning
- ❌ No `__call()` or `__get()` magic methods in framework code
- ✅ Constructor injection with typed parameters
- ✅ Explicit controller registration in `public/index.php`
- ✅ Traceable execution flow (every method call is visible)

**Why**: If you can't Command+Click to the definition, it's magic. Magic breaks IDEs, breaks static analysis, and breaks understanding.

---

### 2. Strict Typing Everywhere

**Definition**: Every parameter, return type, and property is explicitly typed. No escape hatches.

**Rules**:
- ❌ No `mixed` types (except in truly polymorphic contexts)
- ❌ No arrays of unknown structure (use DTOs)
- ❌ No `@param array $config` docblocks hiding structure
- ✅ `declare(strict_types=1);` in every file
- ✅ Type hints on all parameters and return values
- ✅ Property types declared (leveraging PHP 8.5 features)

**Why**: Type errors should be caught at development time, not in production. Strict types turn runtime errors into IDE warnings.

---

### 3. Attributes Over Configuration

**Definition**: Routing, middleware, and validation are declared where they're used, not in distant config files.

**Rules**:
- ❌ No `routes/web.php` files disconnected from controllers
- ❌ No YAML/XML configuration for what PHP can express
- ✅ `#[Route('GET', '/users')]` on controller methods
- ✅ `#[Middleware(AuthMiddleware::class)]` where protection is needed
- ✅ `#[Required]` and `#[Email]` on DTO properties

**Why**: When you read a controller method, you should see its route, middleware, and validation without opening 3 other files.

---

### 4. SQL as a First-Class Citizen

**Definition**: SQL is not shameful. Write it, version it, understand it.

**Rules**:
- ❌ No "migrations" that are just ORM abstractions (`Schema::create()`)
- ❌ No Active Record hiding queries (`$user->save()`)
- ❌ No query builder pretending SQL doesn't exist
- ✅ Raw SQL in `.up.sql` and `.down.sql` files
- ✅ Repository pattern with explicit queries
- ✅ PDO wrapper, not an abstraction layer

**Why**: Complex queries break ORMs. When your application grows, you'll rewrite them as raw SQL anyway. Start honest.

---

### 5. Explicit Over Convenient

**Definition**: If it saves 2 lines but hides behavior, reject it.

**Rules**:
- ❌ No implicit route model binding (`function show(User $user)`)
- ❌ No automatic timestamps (`created_at`/`updated_at` magic)
- ❌ No "convention over configuration" discovery
- ✅ Inject `UserRepository`, call `->find($id)`, handle null
- ✅ Set timestamps explicitly in SQL or application code
- ✅ Register everything explicitly

**Why**: Convenience hides complexity. When debugging at 2am, you want to trace the code, not guess what magic is running.

---

### 6. Modern PHP First

**Definition**: Leverage PHP 8.5+ features to their fullest. Don't write PHP 5 code in PHP 8.

**Rules**:
- ❌ No `private $id; public function getId()` boilerplate
- ❌ No constructor property assignment boilerplate
- ❌ No backwards compatibility with PHP 7
- ✅ Asymmetric visibility: `public private(set) int $id`
- ✅ Constructor property promotion
- ✅ Attributes for metadata
- ✅ Readonly properties where immutability is needed

**Why**: Modern PHP is expressive. If you're not using its features, you might as well use Go.

---

## What We're Building Against

This framework is a **rejection** of common "developer experience" patterns:

| ❌ What Others Do | ✅ What We Do |
|------------------|--------------|
| `Route::get('/users', [UserController::class, 'index'])` in routes file | `#[Route('GET', '/users')]` on the method itself |
| `User::find($id)` hiding a query | `$userRepo->find($id)` explicit repository call |
| `app(UserService::class)` global helper | Constructor injection `__construct(UserService $service)` |
| `Schema::create('users', ...)` migration | Raw SQL: `CREATE TABLE users (...)` |
| `return view('users.index', $data)` | `return Response::json($data)` explicit types |
| Auto-discover controllers in `/app` | Explicit: `$app->registerControllers([UserController::class])` |

---

## Decision Framework

When considering a new feature, ask:

### ✅ Add it if:
- Eliminates genuine boilerplate without hiding behavior
- Makes types more strict or explicit
- Leverages PHP 8.5+ features properly
- Can be traced in an IDE without magic

### ❌ Reject it if:
- Saves 2 lines but requires "just trust me" understanding
- Introduces global state or singletons
- Requires runtime reflection for normal operation
- Works by convention rather than declaration
- Breaks static analysis or IDE navigation

---

## Target Audience

This framework is for:
- Senior developers tired of debugging magic
- Teams maintaining long-lived applications (5+ years)
- Developers who want to understand, not just use
- Projects where "How does this work?" matters

This framework is **not** for:
- Rapid prototyping hackathons
- Developers who want Laravel but lighter
- Projects prioritizing "ship fast" over "understand deeply"

---

**Remember**: We're not building the fastest framework. We're building the most **honest** one.
