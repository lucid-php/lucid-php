<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Mail\Mail;
use Core\Mail\LogMailer;
use Core\Mail\ArrayMailer;
use Core\Log\Logger;
use Core\Log\Handler\StderrHandler;
use PHPUnit\Framework\TestCase;

class MailTest extends TestCase
{
    public function test_can_create_mail(): void
    {
        $mail = Mail::create(
            to: 'user@example.com',
            subject: 'Test Email',
            body: 'This is a test email'
        );

        $this->assertSame('user@example.com', $mail->to);
        $this->assertSame('Test Email', $mail->subject);
        $this->assertSame('This is a test email', $mail->body);
        $this->assertSame('', $mail->from);
        $this->assertFalse($mail->isHtml);
    }

    public function test_can_set_from_address(): void
    {
        $mail = Mail::create('user@example.com', 'Subject', 'Body')
            ->withFrom('sender@example.com');

        $this->assertSame('sender@example.com', $mail->from);
    }

    public function test_can_set_reply_to(): void
    {
        $mail = Mail::create('user@example.com', 'Subject', 'Body')
            ->withReplyTo('reply@example.com');

        $this->assertSame('reply@example.com', $mail->replyTo);
    }

    public function test_can_set_cc_and_bcc(): void
    {
        $mail = Mail::create('user@example.com', 'Subject', 'Body')
            ->withCc(['cc1@example.com', 'cc2@example.com'])
            ->withBcc(['bcc1@example.com']);

        $this->assertSame(['cc1@example.com', 'cc2@example.com'], $mail->cc);
        $this->assertSame(['bcc1@example.com'], $mail->bcc);
    }

    public function test_can_set_html_format(): void
    {
        $mail = Mail::create('user@example.com', 'Subject', '<h1>HTML Body</h1>')
            ->asHtml();

        $this->assertTrue($mail->isHtml);
    }

    public function test_mail_is_immutable(): void
    {
        $original = Mail::create('user@example.com', 'Subject', 'Body');
        $modified = $original->withFrom('sender@example.com');

        $this->assertSame('', $original->from);
        $this->assertSame('sender@example.com', $modified->from);
        $this->assertNotSame($original, $modified);
    }

    public function test_array_mailer_stores_emails(): void
    {
        $mailer = new ArrayMailer('from@example.com');

        $mail1 = Mail::create('user1@example.com', 'Subject 1', 'Body 1');
        $mail2 = Mail::create('user2@example.com', 'Subject 2', 'Body 2');

        $mailer->send($mail1);
        $mailer->send($mail2);

        $this->assertSame(2, $mailer->count());
        $sent = $mailer->getSent();
        $this->assertCount(2, $sent);
        $this->assertSame('user1@example.com', $sent[0]->to);
        $this->assertSame('user2@example.com', $sent[1]->to);
        $this->assertSame('from@example.com', $sent[0]->from);
    }

    public function test_array_mailer_can_clear(): void
    {
        $mailer = new ArrayMailer();
        $mailer->send(Mail::create('user@example.com', 'Subject', 'Body'));
        
        $this->assertSame(1, $mailer->count());
        
        $mailer->clear();
        
        $this->assertSame(0, $mailer->count());
        $this->assertEmpty($mailer->getSent());
    }

    public function test_log_mailer_logs_email(): void
    {
        $logger = new Logger(handlers: [new StderrHandler()]);
        $mailer = new LogMailer($logger, 'from@example.com');

        $mail = Mail::create('user@example.com', 'Test Subject', 'Test Body')
            ->asHtml();

        // Should not throw
        $mailer->send($mail);

        $this->assertTrue(true);
    }

    public function test_array_mailer_uses_default_from(): void
    {
        $mailer = new ArrayMailer('default@example.com');
        $mail = Mail::create('user@example.com', 'Subject', 'Body');

        $mailer->send($mail);

        $sent = $mailer->getSent();
        $this->assertSame('default@example.com', $sent[0]->from);
    }

    public function test_mail_with_explicit_from_overrides_default(): void
    {
        $mailer = new ArrayMailer('default@example.com');
        $mail = Mail::create('user@example.com', 'Subject', 'Body')
            ->withFrom('custom@example.com');

        $mailer->send($mail);

        $sent = $mailer->getSent();
        $this->assertSame('custom@example.com', $sent[0]->from);
    }
}
