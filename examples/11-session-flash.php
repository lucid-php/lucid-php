<?php

declare(strict_types=1);

/**
 * Example 11: Session & Flash Messages
 * 
 * Demonstrates:
 * - Session management
 * - Storing and retrieving session data
 * - Flash messages (one-time messages)
 * - Session security (regeneration)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Session\Session;
use Core\Session\FlashMessages;

echo "Session & Flash Messages Examples:\n";
echo "===================================\n\n";

// ===========================
// Example 1: Basic Session Operations
// ===========================

echo "=== Example 1: Basic Session Operations ===\n\n";

// Create and start session
$session = new Session([
    'name' => 'example_session',
    'cookie_lifetime' => 0,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

$session->start();
echo "✓ Session started (ID: " . substr($session->getId(), 0, 8) . "...)\n\n";

// Store data
$session->set('user_id', 123);
$session->set('username', 'john_doe');
$session->set('theme', 'dark');

echo "Stored session data:\n";
echo "  user_id: " . $session->get('user_id') . "\n";
echo "  username: " . $session->get('username') . "\n";
echo "  theme: " . $session->get('theme') . "\n\n";

// Get with default value
$language = $session->get('language', 'en');
echo "Language (with default): $language\n\n";

// Check if key exists
if ($session->has('user_id')) {
    echo "✓ User is logged in (user_id exists)\n\n";
}

// ===========================
// Example 2: Session Data Management
// ===========================

echo "=== Example 2: Session Data Management ===\n\n";

// Get all session data
$allData = $session->all();
echo "All session data:\n";
foreach ($allData as $key => $value) {
    echo "  $key => " . (is_scalar($value) ? $value : json_encode($value)) . "\n";
}
echo "\n";

// Remove specific key
$session->remove('theme');
echo "✓ Removed 'theme' from session\n";
echo "  Has theme: " . ($session->has('theme') ? 'yes' : 'no') . "\n\n";

// ===========================
// Example 3: Flash Messages
// ===========================

echo "=== Example 3: Flash Messages ===\n\n";

$flash = new FlashMessages($session);

// Add different types of flash messages
$flash->success('User created successfully!');
$flash->error('Failed to send email');
$flash->info('Please verify your email address');
$flash->warning('Your password will expire in 7 days');

echo "Added flash messages\n\n";

// Retrieve messages (they're removed after retrieval)
$successMessages = $flash->get('success');
echo "Success messages:\n";
foreach ($successMessages as $msg) {
    echo "  ✓ $msg\n";
}
echo "\n";

$errorMessages = $flash->get('error');
echo "Error messages:\n";
foreach ($errorMessages as $msg) {
    echo "  ✗ $msg\n";
}
echo "\n";

// Second retrieval returns empty (messages are consumed)
$successMessages2 = $flash->get('success');
echo "Success messages (2nd retrieval): ";
echo empty($successMessages2) ? "[] (removed after first retrieval)\n\n" : "Not empty\n\n";

// ===========================
// Example 4: Multiple Flash Messages
// ===========================

echo "=== Example 4: Multiple Flash Messages ===\n\n";

$flash->success('Step 1 completed');
$flash->success('Step 2 completed');
$flash->success('Step 3 completed');

$messages = $flash->get('success');
echo "Multiple success messages (" . count($messages) . " total):\n";
foreach ($messages as $i => $msg) {
    echo "  " . ($i + 1) . ". $msg\n";
}
echo "\n";

// ===========================
// Example 5: Get All Messages
// ===========================

echo "=== Example 5: Get All Messages at Once ===\n\n";

$flash->success('Operation completed');
$flash->error('Warning: Disk space low');
$flash->info('New features available');

$allMessages = $flash->getAll();
echo "All flash messages by type:\n";
foreach ($allMessages as $type => $messages) {
    echo "  $type:\n";
    foreach ($messages as $msg) {
        echo "    - $msg\n";
    }
}
echo "\n";

// ===========================
// Example 6: Check for Messages
// ===========================

echo "=== Example 6: Check for Messages ===\n\n";

$flash->error('Something went wrong');

if ($flash->has('error')) {
    echo "✓ Error messages exist\n";
    $errors = $flash->get('error');
    echo "  First error: " . $errors[0] . "\n";
}
echo "\n";

if ($flash->hasAny()) {
    echo "✓ Some messages exist\n";
} else {
    echo "✓ No messages in flash storage\n";
}
echo "\n";

// ===========================
// Example 7: Session Security
// ===========================

echo "=== Example 7: Session Security ===\n\n";

$oldId = $session->getId();
echo "Current session ID: " . substr($oldId, 0, 16) . "...\n";

// Regenerate session ID (important after login)
$session->regenerate();
$newId = $session->getId();

echo "New session ID: " . substr($newId, 0, 16) . "...\n";
echo "✓ Session ID regenerated (prevents session fixation)\n\n";

// Data is preserved after regeneration
echo "User ID after regeneration: " . $session->get('user_id') . "\n\n";

// ===========================
// Example 8: Real-World Use Case - Login
// ===========================

echo "=== Example 8: Login Flow ===\n\n";

// Simulate login
class LoginService
{
    public function __construct(
        private Session $session,
        private FlashMessages $flash
    ) {}
    
    public function login(string $username, string $password): bool
    {
        // Validate credentials (simplified)
        if ($username === 'admin' && $password === 'secret') {
            // Important: Regenerate session ID after authentication
            $this->session->regenerate();
            
            // Store user data
            $this->session->set('user_id', 1);
            $this->session->set('username', $username);
            $this->session->set('login_time', time());
            
            // Set success message
            $this->flash->success('Login successful! Welcome back.');
            
            return true;
        }
        
        $this->flash->error('Invalid username or password');
        return false;
    }
    
    public function logout(): void
    {
        $this->flash->info('You have been logged out');
        $this->session->destroy();
    }
    
    public function isLoggedIn(): bool
    {
        return $this->session->has('user_id');
    }
}

$loginService = new LoginService($session, $flash);

// Attempt login
echo "Attempting login...\n";
$success = $loginService->login('admin', 'secret');

if ($success) {
    echo "✓ Login successful\n";
    echo "  User ID: " . $session->get('user_id') . "\n";
    echo "  Username: " . $session->get('username') . "\n";
    
    $messages = $flash->get('success');
    if (!empty($messages)) {
        echo "  Message: " . $messages[0] . "\n";
    }
}
echo "\n";

// ===========================
// Example 9: Shopping Cart
// ===========================

echo "=== Example 9: Shopping Cart in Session ===\n\n";

class ShoppingCart
{
    public function __construct(private Session $session) {}
    
    public function addItem(int $productId, int $quantity): void
    {
        $cart = $this->session->get('cart', []);
        
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity
            ];
        }
        
        $this->session->set('cart', $cart);
    }
    
    public function getItems(): array
    {
        return $this->session->get('cart', []);
    }
    
    public function getTotalItems(): int
    {
        $cart = $this->getItems();
        return array_sum(array_column($cart, 'quantity'));
    }
    
    public function clear(): void
    {
        $this->session->remove('cart');
    }
}

$cart = new ShoppingCart($session);

$cart->addItem(101, 2); // Product 101, quantity 2
$cart->addItem(102, 1); // Product 102, quantity 1
$cart->addItem(101, 1); // Add 1 more of product 101

echo "Shopping cart:\n";
foreach ($cart->getItems() as $item) {
    echo "  Product #{$item['product_id']}: {$item['quantity']} items\n";
}
echo "\n";
echo "Total items in cart: " . $cart->getTotalItems() . "\n\n";

// ===========================
// Example 10: Clear and Destroy
// ===========================

echo "=== Example 10: Clear vs Destroy ===\n\n";

$session->set('temp1', 'value1');
$session->set('temp2', 'value2');

echo "Session has " . count($session->all()) . " keys\n";

// Clear removes all data but keeps session active
$session->clear();
echo "After clear(): " . count($session->all()) . " keys (session still active)\n";
echo "  Is started: " . ($session->isStarted() ? 'yes' : 'no') . "\n\n";

// Destroy completely removes the session
$session->destroy();
echo "After destroy():\n";
echo "  Is started: " . ($session->isStarted() ? 'yes' : 'no') . "\n\n";

// ===========================
// Configuration
// ===========================

echo "=== Configuration (config/session.php) ===\n\n";

echo "return [\n";
echo "    'name' => 'framework_session',\n";
echo "    'cookie_lifetime' => 0,           // 0 = until browser closes\n";
echo "    'cookie_path' => '/',\n";
echo "    'cookie_secure' => true,          // HTTPS only in production\n";
echo "    'cookie_httponly' => true,        // Prevents JavaScript access (XSS)\n";
echo "    'cookie_samesite' => 'Lax',       // CSRF protection\n";
echo "    'use_strict_mode' => true,        // Prevents session fixation\n";
echo "    'gc_maxlifetime' => 1440,         // 24 minutes\n";
echo "];\n\n";

// ===========================
// Best Practices
// ===========================

echo "=== Best Practices ===\n\n";

echo "1. Always regenerate session ID after authentication\n";
echo "   ✓ \$session->regenerate() after login\n";
echo "   ✓ Prevents session fixation attacks\n\n";

echo "2. Use flash messages for one-time notifications\n";
echo "   ✓ Success messages after form submissions\n";
echo "   ✓ Error messages after failed operations\n";
echo "   ✓ Automatically removed after display\n\n";

echo "3. Store minimal data in sessions\n";
echo "   ✓ User ID, username, preferences\n";
echo "   ✗ Don't store large objects or sensitive data\n\n";

echo "4. Configure cookies securely\n";
echo "   ✓ cookie_httponly: true (XSS protection)\n";
echo "   ✓ cookie_secure: true in production (HTTPS only)\n";
echo "   ✓ cookie_samesite: 'Lax' or 'Strict' (CSRF protection)\n\n";

echo "5. Destroy sessions on logout\n";
echo "   ✓ \$session->destroy() to completely remove session\n";
echo "   ✓ Clear sensitive data before redirect\n\n";
