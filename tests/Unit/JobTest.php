<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Job\ProcessOrderJob;
use App\Job\SendWelcomeEmailJob;
use Core\Mail\ArrayMailer;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testSendWelcomeEmailJobSendsHtmlMailToUser(): void
    {
        $mailer = new ArrayMailer();
        $job = new SendWelcomeEmailJob(userId: 1, name: 'Linus', email: 'linus@example.com');

        $job->handle($mailer);

        $this->assertSame(1, $mailer->count());

        $sent = $mailer->getSent()[0];
        $this->assertSame('linus@example.com', $sent->to);
        $this->assertSame('Welcome to Our Platform!', $sent->subject);
        $this->assertTrue($sent->isHtml);
        $this->assertStringContainsString('Welcome, Linus!', $sent->body);
        $this->assertStringContainsString('User ID: 1', $sent->body);
    }

    public function testProcessOrderJobRunsWithoutDependencies(): void
    {
        $job = new ProcessOrderJob(
            orderId: 100,
            total: 49.95,
            items: [
                ['productId' => 1, 'quantity' => 2],
                ['productId' => 5, 'quantity' => 1],
            ],
        );

        $tmp = tempnam(sys_get_temp_dir(), 'lucid_log_');
        $previous = ini_get('error_log');
        ini_set('error_log', $tmp);

        try {
            $job->handle();
        } finally {
            ini_set('error_log', $previous === false ? '' : $previous);
        }

        $output = file_get_contents($tmp) ?: '';
        @unlink($tmp);

        $this->assertStringContainsString('order #100', $output);
        $this->assertStringContainsString('Items: 2', $output);
    }
}
