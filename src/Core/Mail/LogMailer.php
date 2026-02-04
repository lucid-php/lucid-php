<?php

declare(strict_types=1);

namespace Core\Mail;

use Core\Log\LoggerInterface;

class LogMailer implements MailerInterface
{
    private string $defaultFrom;

    public function __construct(
        private LoggerInterface $logger,
        string $defaultFrom = 'noreply@example.com',
    ) {
        $this->defaultFrom = $defaultFrom;
    }

    public function send(Mail $mail): void
    {
        $from = $mail->from ?: $this->defaultFrom;

        $this->logger->info('📧 [EMAIL] Would send email', [
            'from' => $from,
            'to' => $mail->to,
            'subject' => $mail->subject,
            'body_preview' => substr($mail->body, 0, 100),
            'is_html' => $mail->isHtml,
            'cc' => $mail->cc,
            'bcc' => $mail->bcc,
        ]);
    }
}
