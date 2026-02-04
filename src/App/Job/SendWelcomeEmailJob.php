<?php

declare(strict_types=1);

namespace App\Job;

use Core\Mail\Mail;
use Core\Mail\MailerInterface;

/**
 * Send Welcome Email Job
 * 
 * Philosophy:
 * - Jobs are classes with explicit handle() method
 * - Dependencies injected via method parameters (resolved from container)
 * - Job data passed via constructor
 * - Readonly properties for immutability
 */
readonly class SendWelcomeEmailJob
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
    ) {}

    /**
     * Execute the job
     * 
     * Dependencies are automatically resolved from container
     */
    public function handle(MailerInterface $mailer): void
    {
        $mail = Mail::create(
            to: $this->email,
            subject: 'Welcome to Our Platform!',
            body: $this->buildEmailBody(),
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
            <p>We're excited to have you on board.</p>
            <p>User ID: {$this->userId}</p>
            <p>Best regards,<br>The Team</p>
        </body>
        </html>
        HTML;
    }
}

