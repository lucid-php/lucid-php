<?php

declare(strict_types=1);

namespace Core\Mail;

use InvalidArgumentException;

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
        // Validate at the boundary so every construction path (including the
        // with*() copies) is covered. Address and subject fields are emitted
        // verbatim into SMTP commands and mail headers, so a CR/LF would allow
        // header injection (e.g. a forged Bcc) or SMTP command injection (e.g.
        // an extra RCPT TO turning the server into a spam relay). The body may
        // legitimately contain newlines, so it is only checked for null bytes.
        $this->assertAddress($to, 'to');
        if ($from !== '') {
            // Empty $from is allowed; the mailer fills it from config later.
            $this->assertAddress($from, 'from');
        }
        if ($replyTo !== null) {
            $this->assertAddress($replyTo, 'replyTo');
        }
        foreach ($cc as $address) {
            $this->assertAddress((string) $address, 'cc');
        }
        foreach ($bcc as $address) {
            $this->assertAddress((string) $address, 'bcc');
        }
        $this->assertHeaderSafe($subject, 'subject');
        if (str_contains($body, "\0")) {
            throw new InvalidArgumentException('Mail body contains a null byte.');
        }
    }

    /**
     * Reject CR, LF, and null bytes that would break out of a header/command.
     */
    private function assertHeaderSafe(string $value, string $field): void
    {
        if (preg_match('/[\r\n\x00]/', $value) === 1) {
            throw new InvalidArgumentException(
                "Mail {$field} contains line breaks or null bytes (header injection)."
            );
        }
    }

    /**
     * An address must be header-safe and a syntactically valid email.
     */
    private function assertAddress(string $address, string $field): void
    {
        $this->assertHeaderSafe($address, $field);

        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Mail {$field} is not a valid email address: {$address}");
        }
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
