<?php

declare(strict_types=1);

namespace Core\Mail;

interface MailerInterface
{
    public function send(Mail $mail): void;
}
