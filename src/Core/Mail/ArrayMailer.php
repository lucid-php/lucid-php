<?php

declare(strict_types=1);

namespace Core\Mail;

class ArrayMailer implements MailerInterface
{
    private array $sent = [];
    private string $defaultFrom;

    public function __construct(string $defaultFrom = 'test@example.com')
    {
        $this->defaultFrom = $defaultFrom;
    }

    public function send(Mail $mail): void
    {
        $from = $mail->from ?: $this->defaultFrom;
        $this->sent[] = $mail->withFrom($from);
    }

    public function getSent(): array
    {
        return $this->sent;
    }

    public function clear(): void
    {
        $this->sent = [];
    }

    public function count(): int
    {
        return count($this->sent);
    }
}
