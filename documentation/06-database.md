# Database

## Configuration

Set connection details in `config/database.php`. Supported drivers: **SQLite** (default) and **MySQL/MariaDB** (tested with MariaDB 11.8).

## Usage

The `Core\Database\Database` class is a strict PDO wrapper. Inject it into repositories.

```php
class UserRepository
{
    public function __construct(private Database $db) {}

    public function find(int $id): ?User
    {
        $rows = $this->db->query("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        return $rows ? User::fromRow($rows[0]) : null;
    }
}
```

## Migrations

Files: `database/migrations/xxx_name.up.sql` and `xxx_name.down.sql`.

CLI:
- `php bin/migrate up`
- `php bin/migrate down`

## Seeding

Files: `database/seeds/YourSeeder.php` (extend `Core\Database\Seeder`).

CLI:
- `php bin/seed` (Runs default logic)
- `php bin/seed YourSeeder`

## Transactions

The framework provides explicit transaction management for data integrity. All operations are explicit - no auto-commit, no hidden behavior.

### Philosophy

- **Zero Magic:** Explicit begin/commit/rollback calls
- **Type-Safe:** DatabaseException for failures
- **Traceable:** Clear transaction boundaries in code
- **Explicit:** No silent commits or hidden transactions

### Manual Transaction Control

```php
use Core\Database\Database;
use Core\Database\DatabaseException;

$db->beginTransaction();

try {
    // Multiple operations as atomic unit
    $db->execute('INSERT INTO orders (user_id, amount) VALUES (?, ?)', [1, 999]);
    $db->execute('UPDATE users SET balance = balance - ? WHERE id = ?', [999, 1]);
    $db->execute('INSERT INTO audit_log (action) VALUES (?)', ['Order created']);
    
    $db->commit();  // Explicit commit
} catch (\Exception $e) {
    $db->rollback();  // Explicit rollback
    throw $e;
}
```

### Automatic Transaction (Recommended)

```php
// transaction() method handles commit/rollback automatically
$orderId = $db->transaction(function () use ($db) {
    $db->execute('INSERT INTO orders (user_id, amount) VALUES (?, ?)', [1, 999]);
    $orderId = $db->lastInsertId();
    
    $db->execute('UPDATE users SET balance = balance - ? WHERE id = ?', [999, 1]);
    
    return $orderId;  // Returned value available after commit
});

// If callback throws exception, transaction is rolled back automatically
```

### Transaction Methods

```php
$db->beginTransaction(): void       // Start transaction
$db->commit(): void                 // Commit changes (makes permanent)
$db->rollback(): void               // Discard changes
$db->inTransaction(): bool          // Check if currently in transaction
$db->transaction(callable): mixed   // Execute callback in transaction
```

### Common Use Cases

```php
// 1. Money Transfer (requires atomicity)
$db->transaction(function () use ($db, $fromId, $toId, $amount) {
    // Check balance
    $balance = $db->query('SELECT balance FROM users WHERE id = ?', [$fromId])[0]['balance'];
    if ($balance < $amount) {
        throw new \Exception('Insufficient funds');
    }
    
    // Deduct from sender
    $db->execute('UPDATE users SET balance = balance - ? WHERE id = ?', [$amount, $fromId]);
    
    // Add to receiver
    $db->execute('UPDATE users SET balance = balance + ? WHERE id = ?', [$amount, $toId]);
});

// 2. Create Order with Stock Update
$db->transaction(function () use ($db, $productId, $quantity) {
    // Reserve stock
    $db->execute('UPDATE products SET stock = stock - ? WHERE id = ?', [$quantity, $productId]);
    
    // Create order
    $db->execute('INSERT INTO orders (product_id, quantity) VALUES (?, ?)', [$productId, $quantity]);
});

// 3. Batch Insert with Validation
$db->transaction(function () use ($db, $users) {
    foreach ($users as $user) {
        $db->execute('INSERT INTO users (name, email) VALUES (?, ?)', [$user['name'], $user['email']]);
    }
});
```

### Error Handling

```php
try {
    $db->transaction(function () use ($db) {
        // Operations that might fail
        $db->execute('INSERT INTO ...');
    });
} catch (DatabaseException $e) {
    // Transaction was rolled back
    // Handle specific database errors
} catch (\Exception $e) {
    // Transaction was rolled back
    // Handle application errors
}
```

### Transaction Protection

```php
// ❌ BAD: Nested transactions not supported
$db->transaction(function () use ($db) {
    $db->beginTransaction();  // Throws DatabaseException
});

// ✅ GOOD: Single transaction boundary
$db->transaction(function () use ($db) {
    $db->execute('...');
    $db->execute('...');
});
```
