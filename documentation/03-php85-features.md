# PHP 8.5 Features

This framework showcases PHP 8.5's major features:

## 🔗 Native URI Extension
Replace `parse_url()` with RFC 3986-compliant URI parsing:
```php
// Router.php
use Uri\Rfc3986\Uri;

$uri = (new Uri($request->uri))->getPath();
```

## 🚫 #[\NoDiscard] Attribute
Ensures return values are used:
```php
// Response.php
#[\NoDiscard]
public static function json(array $data, int $status = 200): self
{
    return new self(json_encode($data), $status, ['Content-Type' => 'application/json']);
}

// Forces explicit usage:
return Response::json(['success' => true]);  // ✅ OK
Response::json(['success' => true]);          // ⚠️  Warning
(void) Response::json(['success' => true]);   // ✅ OK (intentional discard)
```

## 🧬 Clone-With for Readonly Classes
Immutable updates without boilerplate:
```php
readonly class CreateUserDTO implements ValidatedDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password
    ) {}

    // PHP 8.5: Clone-with reduces boilerplate
    public function withName(string $name): self
    {
        return clone($this, ['name' => $name]);
    }
}

// Usage
$dto = new CreateUserDTO('John', 'john@example.com', 'secret');
$updated = $dto->withName('Jane');  // New instance with updated name
```

## 📦 array_first() / array_last()
Native array helpers:
```php
// UserRepository.php
$rows = $this->db->query("SELECT * FROM users WHERE email = ?", [$email]);
$row = array_first($rows);  // Returns first element or null

// Before PHP 8.5:
$row = $rows[0] ?? null;
$row = !empty($rows) ? $rows[0] : null;
```

## 🔀 Pipe Operator (Future Consideration)
The pipe operator (`|>`) is available in PHP 8.5 for chaining transformations. This framework currently uses clean functional patterns (`array_map`, `array_filter`) and doesn't have deeply nested function calls that would benefit from piping. The pipe operator will be adopted when natural patterns emerge, such as:

```php
// When we have string manipulation chains:
$slug = $title
    |> trim(...)
    |> strtolower(...)
    |> (fn($s) => str_replace(' ', '-', $s));

// Or complex data transformations:
$result = $data
    |> array_filter(...)
    |> array_values(...)
    |> (fn($arr) => array_slice($arr, 0, 10));
```

## 🔒 Final Constructor Properties
Prevent property modification for stronger immutability:
```php
class Router
{
    public function __construct(private final Container $container) {}
    // $container cannot be reassigned after construction
}
```

## ⚡ Performance

All PHP 8.5 features provide excellent performance. Run benchmarks to see the results:

```bash
make benchmark
```

**Key findings:**
- **URI Extension:** Faster when parsing multiple components (1.4x)
- **array_first/last:** Identical or better performance vs traditional patterns
- **Clone-with:** 8% faster than traditional constructor pattern
- **Overall:** Performance differences negligible vs I/O operations

Readability and maintainability are the primary benefits.
