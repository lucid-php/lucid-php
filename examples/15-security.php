<?php

declare(strict_types=1);

/**
 * Example 15: Security Features
 * 
 * Demonstrates:
 * - CSRF protection
 * - Rate limiting
 * - XSS prevention
 * - Security headers
 * - Authentication best practices
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Security\CsrfTokenManager;
use Core\Session\Session;
use Core\RateLimit\RateLimitStore;
use Core\RateLimit\InMemoryRateLimitStore;

echo "Security Features Examples:\n";
echo "===========================\n\n";

// ===========================
// Example 1: CSRF Protection
// ===========================

echo "=== Example 1: CSRF Protection ===\n\n";

// Initialize session and CSRF manager
$session = new Session(['name' => 'security_example']);
$session->start();

$csrf = new CsrfTokenManager($session);

// Generate token for form
$token = $csrf->generateToken();
echo "Generated CSRF token: " . substr($token, 0, 16) . "...\n\n";

echo "HTML Form with CSRF:\n";
echo "<form method=\"POST\" action=\"/profile/update\">\n";
echo "    <input type=\"hidden\" name=\"_csrf\" value=\"<?= \$csrfToken ?>\">\n";
echo "    <input name=\"email\" type=\"email\">\n";
echo "    <button type=\"submit\">Update</button>\n";
echo "</form>\n\n";

// Verify token (simulated)
$submittedToken = $token; // In real scenario, from $_POST['_csrf']
$isValid = $csrf->validateToken($submittedToken);

echo "Token validation: " . ($isValid ? "✓ Valid" : "✗ Invalid") . "\n";
echo "After validation, token is consumed (cannot be reused)\n\n";

// Try to reuse token
$isValid2 = $csrf->validateToken($submittedToken);
echo "Reusing same token: " . ($isValid2 ? "✓ Valid" : "✗ Invalid (expected)") . "\n\n";

// ===========================
// Example 2: CSRF Middleware
// ===========================

echo "=== Example 2: CSRF Middleware ===\n\n";

echo "Protect routes with CSRF middleware:\n\n";

echo "use Core\\Middleware\\CsrfMiddleware;\n\n";

echo "#[RoutePrefix('/admin')]\n";
echo "#[Middleware(CsrfMiddleware::class)]  // All routes protected\n";
echo "class AdminController\n";
echo "{\n";
echo "    #[Route('POST', '/users')]\n";
echo "    public function createUser(Request \$request): Response\n";
echo "    {\n";
echo "        // CSRF automatically verified by middleware\n";
echo "        // Request only reaches here if token is valid\n";
echo "    }\n";
echo "}\n\n";

// ===========================
// Example 3: Rate Limiting
// ===========================

echo "=== Example 3: Rate Limiting ===\n\n";

$store = new InMemoryRateLimitStore();

// Simulate API requests
$key = 'api:user:123';
$maxAttempts = 5;
$window = 60; // seconds

echo "Rate Limiting (max 5 requests per minute):\n\n";

for ($i = 1; $i <= 7; $i++) {
    $count = $store->increment($key, $window);
    
    if ($count <= $maxAttempts) {
        $remaining = $maxAttempts - $count;
        echo "  Request $i: ✓ Allowed (remaining: $remaining)\n";
    } else {
        echo "  Request $i: ✗ Rate limit exceeded\n";
        break;
    }
}
echo "\n";

// ===========================
// Example 4: Rate Limiter Class
// ===========================

echo "=== Example 4: Rate Limit Middleware ===\n\n";

echo "Use #[RateLimit] attribute on routes:\n\n";

echo "use Core\\Attribute\\RateLimit;\n\n";

echo "#[Route('POST', '/api/data')]\n";
echo "#[RateLimit(maxAttempts: 10, window: 60)] // 10 requests per minute\n";
echo "public function apiEndpoint(Request \$request): Response\n";
echo "{\n";
echo "    // Rate limiting handled automatically\n";
echo "    return Response::json(['data' => 'value']);\n";
echo "}\n\n";

echo "Configuration (config/ratelimit.php):\n\n";
echo "return [\n";
echo "    'enabled' => true,\n";
echo "    'identifier_header' => 'REMOTE_ADDR', // Use client IP\n";
echo "];\n\n";

// ===========================
// Example 5: Login Rate Limiting
// ===========================

echo "=== Example 5: Login Rate Limiting ===\n\n";

$store2 = new InMemoryRateLimitStore();

echo "Login attempts (max 5 per 15 minutes):\n\n";

$username = 'john@example.com';
$key = "login:$username";
$maxAttempts = 5;
$window = 900; // 15 minutes

for ($i = 1; $i <= 6; $i++) {
    $count = $store2->increment($key, $window);
    
    if ($count <= $maxAttempts) {
        echo "  Attempt $i: Login attempt allowed\n";
    } else {
        $resetTime = $store2->getResetTime($key);
        $secondsRemaining = $resetTime - time();
        echo "  Attempt $i: ✗ Too many failed attempts. Locked out.\n";
        echo "  Remaining attempts: 0\n";
        echo "  Try again in " . ceil($secondsRemaining / 60) . " minutes\n";
        break;
    }
}
echo "\n";

// ===========================
// Example 6: XSS Prevention
// ===========================

echo "=== Example 6: XSS Prevention ===\n\n";

$userInput = '<script>alert("XSS")</script><b>Safe text</b>';

echo "User input: $userInput\n\n";

echo "Escaped output:\n";
$escaped = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
echo "  $escaped\n\n";

echo "In views, always escape:\n";
echo "  <?= htmlspecialchars(\$user->name) ?>\n";
echo "  <?= htmlspecialchars(\$comment->body) ?>\n\n";

// ===========================
// Example 7: Security Headers
// ===========================

echo "=== Example 7: Security Headers ===\n\n";

echo "Add security headers to all responses:\n\n";

echo "class SecurityHeadersMiddleware\n";
echo "{\n";
echo "    public function process(Request \$request, RequestHandlerInterface \$handler): Response\n";
echo "    {\n";
echo "        \$response = \$handler->handle(\$request);\n";
echo "        \n";
echo "        // Prevent clickjacking\n";
echo "        \$response->withHeader('X-Frame-Options', 'DENY');\n";
echo "        \n";
echo "        // Prevent MIME sniffing\n";
echo "        \$response->withHeader('X-Content-Type-Options', 'nosniff');\n";
echo "        \n";
echo "        // XSS protection\n";
echo "        \$response->withHeader('X-XSS-Protection', '1; mode=block');\n";
echo "        \n";
echo "        // Content Security Policy\n";
echo "        \$response->withHeader('Content-Security-Policy', \"default-src 'self'\");\n";
echo "        \n";
echo "        // HTTPS only\n";
echo "        \$response->withHeader('Strict-Transport-Security', 'max-age=31536000');\n";
echo "        \n";
echo "        return \$response;\n";
echo "    }\n";
echo "}\n\n";

// ===========================
// Example 8: Password Security
// ===========================

echo "=== Example 8: Password Hashing ===\n\n";

$password = 'user-password-123';

// Hash password
$hash = password_hash($password, PASSWORD_ARGON2ID);
echo "Password hash: " . substr($hash, 0, 30) . "...\n\n";

// Verify password
$isValid = password_verify($password, $hash);
echo "Password verification: " . ($isValid ? "✓ Valid" : "✗ Invalid") . "\n\n";

echo "Best practices:\n";
echo "  ✓ Use PASSWORD_ARGON2ID (or PASSWORD_BCRYPT)\n";
echo "  ✓ Never store plaintext passwords\n";
echo "  ✓ Use password_verify() to check passwords\n";
echo "  ✗ Don't use MD5 or SHA1 for passwords\n\n";

// ===========================
// Example 9: SQL Injection Prevention
// ===========================

echo "=== Example 9: SQL Injection Prevention ===\n\n";

echo "Always use prepared statements:\n\n";

echo "✓ SAFE:\n";
echo "\$stmt = \$db->prepare('SELECT * FROM users WHERE email = ?');\n";
echo "\$stmt->execute([\$email]);\n\n";

echo "✗ UNSAFE:\n";
echo "\$sql = \"SELECT * FROM users WHERE email = '{\$email}'\";\n";
echo "\$db->query(\$sql); // Vulnerable to SQL injection!\n\n";

// ===========================
// Example 10: Authentication Best Practices
// ===========================

echo "=== Example 10: Authentication Best Practices ===\n\n";

echo "class AuthController\n";
echo "{\n";
echo "    public function login(LoginDTO \$data, Session \$session, RateLimitStore \$store): Response\n";
echo "    {\n";
echo "        \$key = 'login:' . \$data->email;\n";
echo "        \n";
echo "        // 1. Rate limit login attempts\n";
echo "        if (\$store->increment(\$key, 900) > 5) { // 5 attempts per 15 min\n";
echo "            return Response::json(['error' => 'Too many attempts'], 429);\n";
echo "        }\n";
echo "        \n";
echo "        // 2. Verify credentials\n";
echo "        \$user = \$this->users->findByEmail(\$data->email);\n";
echo "        \n";
echo "        if (!\$user || !password_verify(\$data->password, \$user->password)) {\n";
echo "            return Response::json(['error' => 'Invalid credentials'], 401);\n";
echo "        }\n";
echo "        \n";
echo "        // 3. Regenerate session ID (prevent session fixation)\n";
echo "        \$session->regenerate();\n";
echo "        \n";
echo "        // 4. Store minimal data in session\n";
echo "        \$session->set('user_id', \$user->id);\n";
echo "        \$session->set('login_time', time());\n";
echo "        \n";
echo "        return Response::json(['success' => true]);\n";
echo "    }\n";
echo "}\n\n";

// ===========================
// Best Practices Summary
// ===========================

echo "=== Security Best Practices ===\n\n";

echo "1. CSRF Protection\n";
echo "   ✓ Use CSRF tokens for all state-changing requests\n";
echo "   ✓ Apply CsrfMiddleware to protected routes\n";
echo "   ✓ Tokens are single-use\n\n";

echo "2. Rate Limiting\n";
echo "   ✓ Limit API requests per user/IP\n";
echo "   ✓ Limit login attempts\n";
echo "   ✓ Return 429 status when exceeded\n\n";

echo "3. XSS Prevention\n";
echo "   ✓ Always escape output: htmlspecialchars()\n";
echo "   ✓ Use Content-Security-Policy header\n";
echo "   ✗ Never trust user input\n\n";

echo "4. SQL Injection Prevention\n";
echo "   ✓ Always use prepared statements\n";
echo "   ✓ Never concatenate SQL with user input\n\n";

echo "5. Password Security\n";
echo "   ✓ Use password_hash() with ARGON2ID or BCRYPT\n";
echo "   ✓ Never store plaintext passwords\n";
echo "   ✓ Enforce minimum password strength\n\n";

echo "6. Session Security\n";
echo "   ✓ Regenerate session ID after login\n";
echo "   ✓ Use httponly and secure cookies\n";
echo "   ✓ Set appropriate session timeout\n\n";

echo "7. Security Headers\n";
echo "   ✓ X-Frame-Options: DENY\n";
echo "   ✓ X-Content-Type-Options: nosniff\n";
echo "   ✓ Strict-Transport-Security (HSTS)\n";
echo "   ✓ Content-Security-Policy\n\n";

// Cleanup
$session->destroy();
