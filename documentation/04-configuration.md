# Configuration

Configuration is stored in **explicit PHP files** that return arrays. No `.env` magic.

## Available Config Files

- `config/app.php` - Application settings (name, environment, debug)
- `config/database.php` - Database connection settings

## Usage

```php
// In public/index.php or anywhere Config is injected
$config = new Config(__DIR__ . '/../config');

// Get a value using dot notation
$driver = $config->get('database.driver'); // 'sqlite'
$host = $config->get('database.mysql.host'); // '127.0.0.1'

// Get with default value
$port = $config->get('database.mysql.port', 3306);

// Check if key exists
if ($config->has('database.mysql.host')) {
    // ...
}

// Get entire config file
$allDbConfig = $config->all('database');
```

## Why PHP Config Files?

- ✅ Explicit: Can Command+Click to the file
- ✅ Type-safe: IDE autocomplete works
- ✅ Versioned: Part of your repository
- ✅ Traceable: No hidden environment variables
- ❌ No `.env` magic or global helpers

## Changing Database Driver

Edit `config/database.php`:
```php
return [
    'driver' => 'mysql', // Change from 'sqlite'
    // ...
];
```
