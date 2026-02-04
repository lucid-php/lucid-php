<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Log\LogLevel;
use PHPUnit\Framework\TestCase;

class LogLevelTest extends TestCase
{
    public function testSeverityOrdering(): void
    {
        $this->assertLessThan(LogLevel::ALERT->severity(), LogLevel::EMERGENCY->severity());
        $this->assertLessThan(LogLevel::CRITICAL->severity(), LogLevel::ALERT->severity());
        $this->assertLessThan(LogLevel::ERROR->severity(), LogLevel::CRITICAL->severity());
        $this->assertLessThan(LogLevel::WARNING->severity(), LogLevel::ERROR->severity());
        $this->assertLessThan(LogLevel::NOTICE->severity(), LogLevel::WARNING->severity());
        $this->assertLessThan(LogLevel::INFO->severity(), LogLevel::NOTICE->severity());
        $this->assertLessThan(LogLevel::DEBUG->severity(), LogLevel::INFO->severity());
    }

    public function testShouldLogWhenAboveMinimum(): void
    {
        $minimumLevel = LogLevel::WARNING;

        $this->assertTrue(LogLevel::EMERGENCY->shouldLog($minimumLevel));
        $this->assertTrue(LogLevel::ERROR->shouldLog($minimumLevel));
        $this->assertTrue(LogLevel::WARNING->shouldLog($minimumLevel));
        $this->assertFalse(LogLevel::INFO->shouldLog($minimumLevel));
        $this->assertFalse(LogLevel::DEBUG->shouldLog($minimumLevel));
    }

    public function testShouldLogWithDebugMinimum(): void
    {
        $minimumLevel = LogLevel::DEBUG;

        // All levels should log when minimum is DEBUG
        $this->assertTrue(LogLevel::EMERGENCY->shouldLog($minimumLevel));
        $this->assertTrue(LogLevel::ERROR->shouldLog($minimumLevel));
        $this->assertTrue(LogLevel::INFO->shouldLog($minimumLevel));
        $this->assertTrue(LogLevel::DEBUG->shouldLog($minimumLevel));
    }

    public function testEnumValues(): void
    {
        $this->assertSame('debug', LogLevel::DEBUG->value);
        $this->assertSame('info', LogLevel::INFO->value);
        $this->assertSame('warning', LogLevel::WARNING->value);
        $this->assertSame('error', LogLevel::ERROR->value);
    }
}
