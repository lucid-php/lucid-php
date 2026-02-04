# Mail System

The framework provides an **explicit mail system** with multiple drivers for sending emails. No magic, no auto-configuration—you explicitly choose your mailer and send emails where you need them.

## Philosophy

- **No Magic:** Explicitly create and send Mail objects—no magic queue or global helper
- **Typed Messages:** Mail objects are strongly-typed, readonly classes
- **Explicit Drivers:** Choose smtp, log, or array driver explicitly in config
- **Testing-Friendly:** ArrayMailer stores emails in memory for assertions
- **Development-Friendly:** LogMailer logs emails instead of sending them
- **Zero Hidden Behavior:** No silent failures, no global mail() function wrapper

## Core Concepts

**Mail is the Message:**
- Readonly class carrying email data (to, subject, body, etc.)
- Immutable with fluent withXxx() methods
- Type-safe with strict property types

**Mailer is the Sender:**
- MailerInterface with simple `send(Mail)` method
- Three implementations: SmtpMailer, LogMailer, ArrayMailer
- Explicitly configured via driver selection

**Driver Selection:**
- `smtp`: Sends via SMTP server (production)
- `log`: Logs emails to logger (development)
- `array`: Stores in memory (testing)

## Setup

### 1. Configure Mail in `config/mail.php`

```php
return [
    // Driver: 'smtp', 'log', or 'array'
    'driver' => getenv('MAIL_DRIVER') ?: 'log',
    
    // Default from address
    'from' => [
        'address' => 'noreply@example.com',
        'name' => 'Example App',
    ],
    
    // SMTP configuration (for 'smtp' driver)
    'smtp' => [
        'host' => 'smtp.mailtrap.io',
        'port' => 2525,
        'username' => 'your-username',
        'password' => 'your-password',
        'encryption' => 'tls', // 'tls', 'ssl', or ''
    ],
];
```

### 2. Register Mailer in `public/index.php`

```php
// Mail System Setup
$mailDriver = $config->get('mail.driver', 'log');
$mailFrom = $config->get('mail.from.address');

if ($mailDriver === 'smtp') {
    $mailer = new SmtpMailer(
        host: $config->get('mail.smtp.host'),
        port: $config->get('mail.smtp.port'),
        username: $config->get('mail.smtp.username'),
        password: $config->get('mail.smtp.password'),
        encryption: $config->get('mail.smtp.encryption'),
        defaultFrom: $mailFrom,
    );
} else {
    // Default: Log mailer for development
    $logger = new Logger(handlers: [new StderrHandler()]);
    $mailer = new LogMailer($logger, $mailFrom);
}

$app->getContainer()->set(MailerInterface::class, $mailer);
```

## Basic Usage

### Creating and Sending Email

```php
use Core\Mail\Mail;
use Core\Mail\MailerInterface;

class ExampleController
{
    public function __construct(
        private MailerInterface $mailer
    ) {}
    
    public function sendEmail(): Response
    {
        $mail = Mail::create(
            to: 'user@example.com',
            subject: 'Welcome!',
            body: 'Thank you for joining our platform.'
        );
        
        $this->mailer->send($mail);
        
        return Response::json(['sent' => true]);
    }
}
```

### HTML Email with Options

```php
$mail = Mail::create(
    to: 'user@example.com',
    subject: 'Welcome to Our Platform',
    body: '<h1>Welcome!</h1><p>Thank you for joining.</p>'
)
->asHtml()
->withReplyTo('support@example.com')
->withCc(['manager@example.com'])
->withBcc(['archive@example.com']);

$mailer->send($mail);
```

### Mail is Immutable

```php
$original = Mail::create('user@example.com', 'Subject', 'Body');
$modified = $original->withFrom('sender@example.com');

// $original->from is still ''
// $modified->from is 'sender@example.com'
// $original !== $modified
```

## Mail Drivers

### SMTP Driver (Production)

Sends emails via SMTP server with authentication:

```php
$mailer = new SmtpMailer(
    host: 'smtp.gmail.com',
    port: 587,
    username: 'your-email@gmail.com',
    password: 'your-app-password',
    encryption: 'tls',
    defaultFrom: 'noreply@example.com'
);

$mailer->send($mail);
```

**Supported Encryption:**
- `'tls'` - STARTTLS (recommended, port 587)
- `'ssl'` - SSL/TLS (port 465)
- `''` - No encryption (port 25, not recommended)

**Popular SMTP Services:**
- **Gmail:** smtp.gmail.com:587 (requires App Password)
- **Mailtrap:** smtp.mailtrap.io:2525 (testing)
- **SendGrid:** smtp.sendgrid.net:587
- **Mailgun:** smtp.mailgun.org:587
- **AWS SES:** email-smtp.region.amazonaws.com:587

### Log Driver (Development)

Logs email details to the logger instead of sending:

```php
$logger = new Logger(handlers: [new StderrHandler()]);
$mailer = new LogMailer($logger, 'noreply@example.com');

$mailer->send($mail);
// Logs: 📧 [EMAIL] Would send email {from, to, subject, body_preview, ...}
```

**When to Use:**
- Local development
- Debugging email flow
- When you don't have SMTP credentials
- Testing email triggers without sending

### Array Driver (Testing)

Stores sent emails in memory for test assertions:

```php
$mailer = new ArrayMailer('noreply@example.com');

$mailer->send(Mail::create('user1@example.com', 'Test 1', 'Body 1'));
$mailer->send(Mail::create('user2@example.com', 'Test 2', 'Body 2'));

// Assert in tests
$this->assertSame(2, $mailer->count());
$sent = $mailer->getSent();
$this->assertSame('user1@example.com', $sent[0]->to);

// Clear for next test
$mailer->clear();
```

**When to Use:**
- Unit tests
- Integration tests
- Asserting email content
- Verifying email was sent

## Integration with Queue System

Jobs can inject `MailerInterface` to send emails in the background:

```php
// src/App/Job/SendWelcomeEmailJob.php
readonly class SendWelcomeEmailJob
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
    ) {}
    
    public function handle(MailerInterface $mailer): void
    {
        $mail = Mail::create(
            to: $this->email,
            subject: 'Welcome to Our Platform!',
            body: $this->buildEmailBody()
        )->asHtml();
        
        $mailer->send($mail);
    }
    
    private function buildEmailBody(): string
    {
        return <<<HTML
        <html>
        <body>
            <h1>Welcome, {$this->name}!</h1>
            <p>Thank you for joining our platform.</p>
        </body>
        </html>
        HTML;
    }
}

// Dispatch from controller or listener
$queue->push(new SendWelcomeEmailJob(
    userId: $user->id,
    name: $user->name,
    email: $user->email
));
```

## API Reference

### Mail Class

**Static Factory:**
```php
Mail::create(string $to, string $subject, string $body): Mail
```

**Fluent Methods (Immutable):**
```php
$mail->withFrom(string $from): Mail
$mail->withReplyTo(string $replyTo): Mail
$mail->withCc(array $cc): Mail
$mail->withBcc(array $bcc): Mail
$mail->asHtml(): Mail  // Sets isHtml = true
```

**Properties (Readonly):**
```php
readonly string $to;
readonly string $subject;
readonly string $body;
readonly string $from;
readonly ?string $replyTo;
readonly array $cc;
readonly array $bcc;
readonly bool $isHtml;
```

### MailerInterface

```php
interface MailerInterface
{
    public function send(Mail $mail): void;
}
```

**Implementations:**
- `SmtpMailer` - Sends via SMTP server
- `LogMailer` - Logs to logger (development)
- `ArrayMailer` - Stores in memory (testing)

## Best Practices

### 1. Use Queue for Emails (Don't Block Requests)

```php
// ❌ BAD: Blocks request until email sent (slow)
public function register(CreateUserDTO $dto, MailerInterface $mailer): Response
{
    $user = $this->users->create($dto);
    
    $mail = Mail::create($user->email, 'Welcome', 'Body');
    $mailer->send($mail);  // Blocks for 1-2 seconds!
    
    return Response::json(['id' => $user->id], 201);
}

// ✅ GOOD: Queue email, return immediately (fast)
public function register(
    CreateUserDTO $dto, 
    QueueInterface $queue
): Response {
    $user = $this->users->create($dto);
    
    $queue->push(new SendWelcomeEmailJob(
        userId: $user->id,
        name: $user->name,
        email: $user->email
    ));
    
    return Response::json(['id' => $user->id], 201);  // Fast!
}
```

### 2. Use ArrayMailer in Tests

```php
// Always use ArrayMailer in test setUp
protected function setUp(): void
{
    $this->mailer = new ArrayMailer();
    $this->container->set(MailerInterface::class, $this->mailer);
}

// Clear between tests
protected function tearDown(): void
{
    $this->mailer->clear();
}
```

### 3. HTML vs Plain Text

```php
// For marketing/styled emails
$mail = Mail::create($to, $subject, '<html>...</html>')->asHtml();

// For transactional/system emails
$mail = Mail::create($to, $subject, 'Plain text message');
```

## Philosophy Compliance

✅ **Zero Magic:** Explicitly create Mail and call send()—no global helpers  
✅ **Strict Typing:** Mail is typed readonly class with strict properties  
✅ **Explicit:** You choose driver in config, create mailer explicitly  
✅ **Attributes-First:** N/A—mail is code-driven  
✅ **Modern PHP:** Uses readonly classes, constructor property promotion  
✅ **Traceable:** Command+Click on MailerInterface takes you to interface
