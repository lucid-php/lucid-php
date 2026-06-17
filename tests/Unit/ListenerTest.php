<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Event\UserCreated;
use App\Event\UserDeleted;
use App\Job\SendWelcomeEmailJob;
use App\Listener\CleanupUserData;
use App\Listener\LogUserCreation;
use App\Listener\SendWelcomeEmail;
use Core\Queue\QueueInterface;
use PHPUnit\Framework\TestCase;

class ListenerTest extends TestCase
{
    public function testSendWelcomeEmailQueuesJobWithEventData(): void
    {
        $event = new UserCreated(userId: 42, name: 'Ada', email: 'ada@example.com');

        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('push')
            ->with($this->callback(function (object $job) use ($event): bool {
                return $job instanceof SendWelcomeEmailJob
                    && $job->userId === $event->userId
                    && $job->name === $event->name
                    && $job->email === $event->email;
            }));

        (new SendWelcomeEmail($queue))->handle($event);
    }

    public function testLogUserCreationWritesAuditLine(): void
    {
        $event = new UserCreated(userId: 7, name: 'Grace', email: 'grace@example.com');

        $output = $this->captureErrorLog(static function () use ($event): void {
            (new LogUserCreation())->handle($event);
        });

        $this->assertStringContainsString('User created', $output);
        $this->assertStringContainsString('ID=7', $output);
        $this->assertStringContainsString('grace@example.com', $output);
    }

    public function testCleanupUserDataLogsDeletedUser(): void
    {
        $event = new UserDeleted(userId: 9, email: 'gone@example.com');

        $output = $this->captureErrorLog(static function () use ($event): void {
            (new CleanupUserData())->handle($event);
        });

        $this->assertStringContainsString('gone@example.com', $output);
    }

    /**
     * Run a callback while redirecting error_log() output to a temp file,
     * then return what was written.
     */
    private function captureErrorLog(callable $callback): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'lucid_log_');
        $previous = ini_get('error_log');
        ini_set('error_log', $tmp);

        try {
            $callback();
        } finally {
            ini_set('error_log', $previous === false ? '' : $previous);
        }

        $contents = file_get_contents($tmp) ?: '';
        @unlink($tmp);

        return $contents;
    }
}
