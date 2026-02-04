<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Job\SendWelcomeEmailJob;
use Core\Mail\ArrayMailer;
use Core\Mail\MailerInterface;
use Core\Container;
use PHPUnit\Framework\TestCase;

class MailSystemTest extends TestCase
{
    public function test_send_welcome_email_job_sends_email(): void
    {
        $container = new Container();
        $mailer = new ArrayMailer('noreply@example.com');
        $container->set(MailerInterface::class, $mailer);

        $job = new SendWelcomeEmailJob(
            userId: 123,
            name: 'John Doe',
            email: 'john@example.com'
        );

        $job->handle($mailer);

        $this->assertSame(1, $mailer->count());
        
        $sent = $mailer->getSent();
        $email = $sent[0];
        
        $this->assertSame('john@example.com', $email->to);
        $this->assertSame('Welcome to Our Platform!', $email->subject);
        $this->assertStringContainsString('John Doe', $email->body);
        $this->assertStringContainsString('User ID: 123', $email->body);
        $this->assertTrue($email->isHtml);
    }

    public function test_multiple_emails_can_be_sent(): void
    {
        $mailer = new ArrayMailer('noreply@example.com');

        $job1 = new SendWelcomeEmailJob(1, 'User One', 'user1@example.com');
        $job2 = new SendWelcomeEmailJob(2, 'User Two', 'user2@example.com');

        $job1->handle($mailer);
        $job2->handle($mailer);

        $this->assertSame(2, $mailer->count());
        
        $sent = $mailer->getSent();
        $this->assertSame('user1@example.com', $sent[0]->to);
        $this->assertSame('user2@example.com', $sent[1]->to);
    }
}
