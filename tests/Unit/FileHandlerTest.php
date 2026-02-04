<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Log\LogEntry;
use Core\Log\LogLevel;
use Core\Log\Handler\FileHandler;
use PHPUnit\Framework\TestCase;

class FileHandlerTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/test-' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testWritesJsonToFile(): void
    {
        $handler = new FileHandler($this->logFile, json: true);
        
        $entry = new LogEntry(
            level: LogLevel::INFO,
            message: 'Test message',
            context: ['key' => 'value'],
            timestamp: new \DateTimeImmutable('2026-01-30 12:00:00')
        );

        $handler->handle($entry);

        $this->assertFileExists($this->logFile);
        $content = file_get_contents($this->logFile);
        
        $decoded = json_decode(trim($content), true);
        $this->assertSame('info', $decoded['level']);
        $this->assertSame('Test message', $decoded['message']);
        $this->assertSame(['key' => 'value'], $decoded['context']);
    }

    public function testWritesPlainTextToFile(): void
    {
        $handler = new FileHandler($this->logFile, json: false);
        
        $entry = new LogEntry(
            level: LogLevel::ERROR,
            message: 'Error occurred',
            context: [],
            timestamp: new \DateTimeImmutable('2026-01-30 12:00:00')
        );

        $handler->handle($entry);

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('ERROR', $content);
        $this->assertStringContainsString('Error occurred', $content);
    }

    public function testAppendsMultipleEntries(): void
    {
        $handler = new FileHandler($this->logFile, json: true);
        
        $entry1 = new LogEntry(
            level: LogLevel::INFO,
            message: 'First',
            context: [],
            timestamp: new \DateTimeImmutable()
        );

        $entry2 = new LogEntry(
            level: LogLevel::INFO,
            message: 'Second',
            context: [],
            timestamp: new \DateTimeImmutable()
        );

        $handler->handle($entry1);
        $handler->handle($entry2);

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES);
        $this->assertCount(2, $lines);
        
        $decoded1 = json_decode($lines[0], true);
        $decoded2 = json_decode($lines[1], true);
        
        $this->assertSame('First', $decoded1['message']);
        $this->assertSame('Second', $decoded2['message']);
    }

    public function testCreatesDirectoryIfNotExists(): void
    {
        $nestedPath = sys_get_temp_dir() . '/test-logs-' . uniqid() . '/nested/app.log';
        $handler = new FileHandler($nestedPath, json: true);
        
        $entry = new LogEntry(
            level: LogLevel::INFO,
            message: 'Test',
            context: [],
            timestamp: new \DateTimeImmutable()
        );

        $handler->handle($entry);

        $this->assertFileExists($nestedPath);
        
        // Cleanup
        unlink($nestedPath);
        rmdir(dirname($nestedPath));
        rmdir(dirname(dirname($nestedPath)));
    }
}
