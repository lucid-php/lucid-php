<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Log\Logger;
use Core\Log\LogEntry;
use Core\Log\LogHandlerInterface;
use Core\Log\LogLevel;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testLogsMessage(): void
    {
        $handler = new class implements LogHandlerInterface {
            public ?LogEntry $lastEntry = null;
            
            public function handle(LogEntry $entry): void {
                $this->lastEntry = $entry;
            }
        };

        $logger = new Logger(LogLevel::DEBUG, [$handler]);
        $logger->info('Test message');

        $this->assertNotNull($handler->lastEntry);
        $this->assertSame('Test message', $handler->lastEntry->message);
        $this->assertSame(LogLevel::INFO, $handler->lastEntry->level);
    }

    public function testInterpolatesContext(): void
    {
        $handler = new class implements LogHandlerInterface {
            public ?LogEntry $lastEntry = null;
            
            public function handle(LogEntry $entry): void {
                $this->lastEntry = $entry;
            }
        };

        $logger = new Logger(LogLevel::DEBUG, [$handler]);
        $logger->info('User {user_id} logged in from {ip}', [
            'user_id' => 42,
            'ip' => '192.168.1.1'
        ]);

        $this->assertSame('User 42 logged in from 192.168.1.1', $handler->lastEntry->message);
        $this->assertSame(42, $handler->lastEntry->context['user_id']);
    }

    public function testRespectsMinimumLevel(): void
    {
        $handler = new class implements LogHandlerInterface {
            public int $callCount = 0;
            
            public function handle(LogEntry $entry): void {
                $this->callCount++;
            }
        };

        $logger = new Logger(LogLevel::WARNING, [$handler]);
        
        $logger->debug('Debug message');  // Should not log
        $logger->info('Info message');    // Should not log
        $logger->warning('Warning message'); // Should log
        $logger->error('Error message');   // Should log

        $this->assertSame(2, $handler->callCount);
    }

    public function testMultipleHandlers(): void
    {
        $handler1 = new class implements LogHandlerInterface {
            public int $callCount = 0;
            public function handle(LogEntry $entry): void {
                $this->callCount++;
            }
        };

        $handler2 = new class implements LogHandlerInterface {
            public int $callCount = 0;
            public function handle(LogEntry $entry): void {
                $this->callCount++;
            }
        };

        $logger = new Logger(LogLevel::DEBUG, [$handler1, $handler2]);
        $logger->info('Test');

        $this->assertSame(1, $handler1->callCount);
        $this->assertSame(1, $handler2->callCount);
    }

    public function testAddHandler(): void
    {
        $handler = new class implements LogHandlerInterface {
            public int $callCount = 0;
            public function handle(LogEntry $entry): void {
                $this->callCount++;
            }
        };

        $logger = new Logger(LogLevel::DEBUG);
        $logger->addHandler($handler);
        $logger->info('Test');

        $this->assertSame(1, $handler->callCount);
    }

    public function testAllLogLevels(): void
    {
        $handler = new class implements LogHandlerInterface {
            public array $levels = [];
            
            public function handle(LogEntry $entry): void {
                $this->levels[] = $entry->level;
            }
        };

        $logger = new Logger(LogLevel::DEBUG, [$handler]);
        
        $logger->emergency('emergency');
        $logger->alert('alert');
        $logger->critical('critical');
        $logger->error('error');
        $logger->warning('warning');
        $logger->notice('notice');
        $logger->info('info');
        $logger->debug('debug');

        $this->assertCount(8, $handler->levels);
        $this->assertSame(LogLevel::EMERGENCY, $handler->levels[0]);
        $this->assertSame(LogLevel::DEBUG, $handler->levels[7]);
    }

    public function testContextPreserved(): void
    {
        $handler = new class implements LogHandlerInterface {
            public ?LogEntry $lastEntry = null;
            
            public function handle(LogEntry $entry): void {
                $this->lastEntry = $entry;
            }
        };

        $logger = new Logger(LogLevel::DEBUG, [$handler]);
        $context = [
            'user_id' => 42,
            'action' => 'login',
            'metadata' => ['browser' => 'Chrome']
        ];
        
        $logger->info('User action', $context);

        $this->assertSame($context, $handler->lastEntry->context);
    }
}
