<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Schedule\FileLock;
use Core\Schedule\Scheduler;
use Core\Schedule\ScheduledTask;
use Core\Console\OutputInterface;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class SchedulerOverlapTest extends TestCase
{
    private string $lockDir;

    protected function setUp(): void
    {
        $this->lockDir = sys_get_temp_dir() . '/lucid_locks_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->lockDir)) {
            foreach (glob($this->lockDir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->lockDir);
        }
    }

    public function testFileLockIsExclusive(): void
    {
        $lock = new FileLock($this->lockDir);

        $this->assertTrue($lock->acquire('task-a'));
        $this->assertFalse($lock->acquire('task-a'), 'second acquire of a held lock fails');

        $lock->release('task-a');
        $this->assertTrue($lock->acquire('task-a'), 'lock can be acquired again after release');
    }

    public function testRunSkipsAnOverlappingTaskWhileItsLockIsHeld(): void
    {
        $lock = new FileLock($this->lockDir);
        $runs = 0;

        $task = new ScheduledTask(
            description: 'report',
            cronExpression: '* * * * *', // always due
            callback: function () use (&$runs): void { $runs++; },
            withoutOverlapping: true,
        );

        $scheduler = new Scheduler($this->silentOutput(), new DateTimeZone('UTC'), $lock);
        $scheduler->task($task);

        // Simulate a prior instance still running by holding the lock.
        $this->assertTrue($lock->acquire('report'));

        $stats = $scheduler->run();

        $this->assertSame(0, $runs, 'task did not run while its lock was held');
        $this->assertSame(1, $stats['skipped']);

        // Release and run again: now it executes and the lock is freed afterward.
        $lock->release('report');
        $stats = $scheduler->run();

        $this->assertSame(1, $runs, 'task ran once the lock was free');
        $this->assertSame(1, $stats['ran']);
        $this->assertTrue($lock->acquire('report'), 'lock released after the task completed');
    }

    public function testThrowsWhenOverlapRequestedWithoutLock(): void
    {
        $task = new ScheduledTask('x', '* * * * *', fn () => null, withoutOverlapping: true);
        $scheduler = new Scheduler($this->silentOutput(), new DateTimeZone('UTC')); // no lock
        $scheduler->task($task);

        $this->expectException(\RuntimeException::class);
        $scheduler->run();
    }

    private function silentOutput(): OutputInterface
    {
        return new class implements OutputInterface {
            public function write(string $message): void {}
            public function writeln(string $message): void {}
            public function info(string $message): void {}
            public function success(string $message): void {}
            public function error(string $message): void {}
            public function warning(string $message): void {}
            public function table(array $headers, array $rows): void {}
        };
    }
}
