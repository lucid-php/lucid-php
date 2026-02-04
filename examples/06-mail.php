<?php

declare(strict_types=1);

/**
 * Example 6: Mail System
 * 
 * Demonstrates:
 * - Sending emails via SMTP
 * - Using different mail drivers (SMTP, Log, Array)
 * - Queued emails
 * - Email with attachments
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Mail\Mail;
use Core\Mail\SmtpMailer;
use Core\Mail\LogMailer;
use Core\Mail\ArrayMailer;
use Core\Mail\MailerInterface;
use Core\Log\Logger;
use Core\Log\LogLevel;

echo "Mail System Examples:\n";
echo "====================\n\n";

// ===========================
// Example 1: Simple Email
// ===========================

echo "=== Example 1: Simple Email ===\n\n";

// Create mailer instance
$logger = new Logger(LogLevel::INFO);
$mailer = new LogMailer($logger);

// Create and send mail
$mail = Mail::create(
    to: 'user@example.com',
    subject: 'Welcome to Our Platform',
    body: 'Thank you for signing up! We\'re excited to have you.'
);

$mailer->send($mail);

echo "Email sent to user@example.com\n\n";

// ===========================
// Example 2: Email with Multiple Recipients
// ===========================

echo "=== Example 2: Multiple Recipients ===\n\n";

$mail = new Mail(
    to: 'john@example.com',
    subject: 'Team Meeting Tomorrow',
    body: 'Reminder: We have a team meeting tomorrow at 10 AM.',
    from: 'noreply@myapp.com',
    cc: ['manager@example.com'],
    bcc: ['admin@example.com']
);

$mailer->send($mail);

echo "Email sent to multiple recipients\n\n";

// ===========================
// Example 3: HTML Email
// ===========================

echo "=== Example 3: HTML Email ===\n\n";

$htmlContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background: #4CAF50; color: white; padding: 20px; }
        .content { padding: 20px; }
        .button { 
            background: #4CAF50; 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Confirmation</h1>
    </div>
    <div class="content">
        <p>Dear Customer,</p>
        <p>Your order #12345 has been confirmed!</p>
        <p>Order Total: \$149.99</p>
        <p><a href="https://example.com/orders/12345" class="button">View Order</a></p>
    </div>
</body>
</html>
HTML;

$mail = new Mail(
    to: 'customer@example.com',
    subject: 'Order Confirmation #12345',
    body: $htmlContent,
    from: 'orders@myapp.com',
    isHtml: true
);

$mailer->send($mail);

echo "HTML email sent\n\n";

// ===========================
// Example 4: Different Mailers
// ===========================

echo "=== Example 4: Different Mail Mailers ===\n\n";

// SMTP Mailer (for production)
echo "SMTP Mailer:\n";
$smtpMailer = new SmtpMailer(
    host: 'smtp.mailtrap.io',
    port: 587,
    username: 'your-username',
    password: 'your-password',
    encryption: 'tls'
);
echo "  - Sends real emails via SMTP server\n";
echo "  - Best for production\n\n";

// Log Mailer (for development)
echo "Log Mailer:\n";
$logMailer = new LogMailer($logger);
$testMail = Mail::create(
    to: 'test@example.com',
    subject: 'Test Email',
    body: 'This will be logged instead of sent'
);
$logMailer->send($testMail);
echo "  - Logs emails to console instead of sending\n";
echo "  - Perfect for local development\n\n";

// Array Mailer (for testing)
echo "Array Mailer:\n";
$arrayMailer = new ArrayMailer();
$testMail2 = Mail::create(
    to: 'test@example.com',
    subject: 'Test Email',
    body: 'This will be stored in memory'
);
$arrayMailer->send($testMail2);
echo "  - Stores emails in memory\n";
echo "  - Perfect for automated tests\n";
echo "  - Retrieve: \$arrayMailer->getSent()\n\n";

// ===========================
// Example 5: Queued Emails
// ===========================

echo "=== Example 5: Queued Emails ===\n\n";

// Create a mail job class
class SendWelcomeEmailJob
{
    public function __construct(
        private string $userEmail,
        private string $userName,
        private MailerInterface $mailer
    ) {}
    
    public function handle(): void
    {
        $mail = Mail::create(
            to: $this->userEmail,
            subject: 'Welcome to Our Platform',
            body: "Hello {$this->userName}! Welcome aboard!"
        );
        $this->mailer->send($mail);
    }
}

echo "Usage:\n";
echo "\$queue->dispatch(new SendWelcomeEmailJob('user@example.com', 'John'));\n\n";
echo "Benefits:\n";
echo "  - Non-blocking: User doesn't wait for email to send\n";
echo "  - Retry logic: Failed emails can be retried\n";
echo "  - Rate limiting: Control sending rate\n";
echo "  - Background processing: Handled by queue worker\n\n";

// ===========================
// Example 6: Email Templates
// ===========================

echo "=== Example 6: Email Templates ===\n\n";

class EmailTemplate
{
    public static function welcome(string $name, string $activationLink): string
    {
        return <<<HTML
        <h1>Welcome, {$name}!</h1>
        <p>Thank you for joining our platform.</p>
        <p>Please activate your account by clicking the link below:</p>
        <a href="{$activationLink}">Activate Account</a>
        HTML;
    }
    
    public static function orderConfirmation(int $orderId, float $total): string
    {
        return <<<HTML
        <h1>Order Confirmation</h1>
        <p>Your order #{$orderId} has been confirmed!</p>
        <p>Total Amount: \${$total}</p>
        <p>We'll notify you when your order ships.</p>
        HTML;
    }
    
    public static function passwordReset(string $resetLink): string
    {
        return <<<HTML
        <h1>Password Reset Request</h1>
        <p>You requested a password reset. Click the link below to continue:</p>
        <a href="{$resetLink}">Reset Password</a>
        <p>This link expires in 1 hour.</p>
        <p>If you didn't request this, please ignore this email.</p>
        HTML;
    }
}

echo "Using templates:\n";
$welcomeMail = Mail::create(
    to: 'user@example.com',
    subject: 'Welcome!',
    body: EmailTemplate::welcome('John', 'https://example.com/activate/token')
);
// $mailer->send($welcomeMail);
echo "Email created with template\n\n";

// ===========================
// Configuration
// ===========================

echo "=== Configuration (config/mail.php) ===\n\n";

echo "return [\n";
echo "    'driver' => env('MAIL_DRIVER', 'log'),\n";
echo "    'from' => [\n";
echo "        'address' => 'noreply@example.com',\n";
echo "        'name' => 'My Application'\n";
echo "    ],\n";
echo "    'smtp' => [\n";
echo "        'host' => env('SMTP_HOST', 'smtp.mailtrap.io'),\n";
echo "        'port' => env('SMTP_PORT', 587),\n";
echo "        'username' => env('SMTP_USERNAME'),\n";
echo "        'password' => env('SMTP_PASSWORD'),\n";
echo "        'encryption' => env('SMTP_ENCRYPTION', 'tls')\n";
echo "    ]\n";
echo "];\n";
