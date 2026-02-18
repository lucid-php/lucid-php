<?php

declare(strict_types=1);

namespace Core\Mail;

use Exception;

class SmtpMailer implements MailerInterface
{
    private string $defaultFrom;

    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        private string $encryption = 'tls', // tls, ssl, or empty
        string $defaultFrom = '',
        private bool $verifyPeer = true, // Enable SSL verification by default for security
        private bool $allowSelfSigned = false, // Disallow self-signed certs by default
    ) {
        $this->defaultFrom = $defaultFrom ?: $username;
    }

    public function send(Mail $mail): void
    {
        // Use mail's from address or default
        $from = $mail->from ?: $this->defaultFrom;
        $mail = $mail->withFrom($from);

        $socket = $this->connect();

        try {
            $this->authenticate($socket);
            $this->sendMail($socket, $mail);
        } finally {
            $this->disconnect($socket);
        }
    }

    private function connect()
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => $this->verifyPeer,
                'verify_peer_name' => $this->verifyPeer,
                'allow_self_signed' => $this->allowSelfSigned,
            ],
        ]);

        $address = $this->encryption === 'ssl'
            ? "ssl://{$this->host}:{$this->port}"
            : "tcp://{$this->host}:{$this->port}";

        $socket = stream_socket_client(
            $address,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new Exception("Failed to connect to SMTP server: {$errstr} ({$errno})");
        }

        $this->getResponse($socket); // Read greeting

        $this->sendCommand($socket, "EHLO {$this->host}");

        // Start TLS if required
        if ($this->encryption === 'tls') {
            $this->sendCommand($socket, "STARTTLS");
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable TLS encryption");
            }
            $this->sendCommand($socket, "EHLO {$this->host}");
        }

        return $socket;
    }

    private function authenticate($socket): void
    {
        $this->sendCommand($socket, "AUTH LOGIN");
        $this->sendCommand($socket, base64_encode($this->username));
        $this->sendCommand($socket, base64_encode($this->password));
    }

    private function sendMail($socket, Mail $mail): void
    {
        // MAIL FROM
        $this->sendCommand($socket, "MAIL FROM:<{$mail->from}>");

        // RCPT TO
        $this->sendCommand($socket, "RCPT TO:<{$mail->to}>");
        foreach ($mail->cc as $cc) {
            $this->sendCommand($socket, "RCPT TO:<{$cc}>");
        }
        foreach ($mail->bcc as $bcc) {
            $this->sendCommand($socket, "RCPT TO:<{$bcc}>");
        }

        // DATA
        $this->sendCommand($socket, "DATA");

        // Headers and body
        $message = $this->buildMessage($mail);
        fwrite($socket, $message . "\r\n.\r\n");
        $this->getResponse($socket);
    }

    private function buildMessage(Mail $mail): string
    {
        $headers = [
            "From: {$mail->from}",
            "To: {$mail->to}",
            "Subject: {$mail->subject}",
            "MIME-Version: 1.0",
        ];

        if ($mail->replyTo) {
            $headers[] = "Reply-To: {$mail->replyTo}";
        }

        if (!empty($mail->cc)) {
            $headers[] = "Cc: " . implode(', ', $mail->cc);
        }

        if ($mail->isHtml) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $mail->body;
    }

    private function sendCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
        $this->getResponse($socket);
    }

    private function getResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if ($code >= 400) {
            throw new Exception("SMTP error: {$response}");
        }

        return $response;
    }

    private function disconnect($socket): void
    {
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
    }
}
