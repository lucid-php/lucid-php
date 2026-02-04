<?php

declare(strict_types=1);

namespace Core\Mail;

readonly class Mail
{
    public function __construct(
        public string $to,
        public string $subject,
        public string $body,
        public string $from,
        public ?string $replyTo = null,
        public array $cc = [],
        public array $bcc = [],
        public bool $isHtml = false,
    ) {
    }

    public static function create(string $to, string $subject, string $body): self
    {
        return new self(
            to: $to,
            subject: $subject,
            body: $body,
            from: '', // Will be set by mailer from config
        );
    }

    public function withFrom(string $from): self
    {
        return new self(
            to: $this->to,
            subject: $this->subject,
            body: $this->body,
            from: $from,
            replyTo: $this->replyTo,
            cc: $this->cc,
            bcc: $this->bcc,
            isHtml: $this->isHtml,
        );
    }

    public function withReplyTo(string $replyTo): self
    {
        return new self(
            to: $this->to,
            subject: $this->subject,
            body: $this->body,
            from: $this->from,
            replyTo: $replyTo,
            cc: $this->cc,
            bcc: $this->bcc,
            isHtml: $this->isHtml,
        );
    }

    public function withCc(array $cc): self
    {
        return new self(
            to: $this->to,
            subject: $this->subject,
            body: $this->body,
            from: $this->from,
            replyTo: $this->replyTo,
            cc: $cc,
            bcc: $this->bcc,
            isHtml: $this->isHtml,
        );
    }

    public function withBcc(array $bcc): self
    {
        return new self(
            to: $this->to,
            subject: $this->subject,
            body: $this->body,
            from: $this->from,
            replyTo: $this->replyTo,
            cc: $this->cc,
            bcc: $bcc,
            isHtml: $this->isHtml,
        );
    }

    public function asHtml(): self
    {
        return new self(
            to: $this->to,
            subject: $this->subject,
            body: $this->body,
            from: $this->from,
            replyTo: $this->replyTo,
            cc: $this->cc,
            bcc: $this->bcc,
            isHtml: true,
        );
    }
}
