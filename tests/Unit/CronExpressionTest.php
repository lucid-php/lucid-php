<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Schedule\CronExpression;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class CronExpressionTest extends TestCase
{
    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->timezone = new DateTimeZone('UTC');
    }

    public function test_every_minute_matches_any_minute(): void
    {
        $expression = CronExpression::everyMinute();
        
        $time1 = new DateTimeImmutable('2026-02-04 10:27:00', $this->timezone);
        $time2 = new DateTimeImmutable('2026-02-04 10:28:00', $this->timezone);
        $time3 = new DateTimeImmutable('2026-02-04 15:43:00', $this->timezone);
        
        $this->assertTrue(CronExpression::matches($expression, $time1));
        $this->assertTrue(CronExpression::matches($expression, $time2));
        $this->assertTrue(CronExpression::matches($expression, $time3));
    }

    public function test_hourly_matches_only_at_zero_minutes(): void
    {
        $expression = CronExpression::hourly();
        
        $match = new DateTimeImmutable('2026-02-04 10:00:00', $this->timezone);
        $noMatch1 = new DateTimeImmutable('2026-02-04 10:01:00', $this->timezone);
        $noMatch2 = new DateTimeImmutable('2026-02-04 10:30:00', $this->timezone);
        
        $this->assertTrue(CronExpression::matches($expression, $match));
        $this->assertFalse(CronExpression::matches($expression, $noMatch1));
        $this->assertFalse(CronExpression::matches($expression, $noMatch2));
    }

    public function test_daily_at_specific_time(): void
    {
        $expression = CronExpression::dailyAt(3, 30);
        
        $match = new DateTimeImmutable('2026-02-04 03:30:00', $this->timezone);
        $noMatch1 = new DateTimeImmutable('2026-02-04 03:31:00', $this->timezone);
        $noMatch2 = new DateTimeImmutable('2026-02-04 04:30:00', $this->timezone);
        
        $this->assertTrue(CronExpression::matches($expression, $match));
        $this->assertFalse(CronExpression::matches($expression, $noMatch1));
        $this->assertFalse(CronExpression::matches($expression, $noMatch2));
    }

    public function test_weekly_on_specific_day(): void
    {
        // Monday at 9 AM
        $expression = CronExpression::weeklyOn(1, 9, 0);
        
        // 2026-02-09 is a Monday
        $match = new DateTimeImmutable('2026-02-09 09:00:00', $this->timezone);
        
        // 2026-02-10 is Tuesday
        $noMatch = new DateTimeImmutable('2026-02-10 09:00:00', $this->timezone);
        
        $this->assertTrue(CronExpression::matches($expression, $match));
        $this->assertFalse(CronExpression::matches($expression, $noMatch));
    }

    public function test_every_five_minutes(): void
    {
        $expression = CronExpression::everyFiveMinutes();
        
        $match1 = new DateTimeImmutable('2026-02-04 10:00:00', $this->timezone);
        $match2 = new DateTimeImmutable('2026-02-04 10:05:00', $this->timezone);
        $match3 = new DateTimeImmutable('2026-02-04 10:10:00', $this->timezone);
        $noMatch = new DateTimeImmutable('2026-02-04 10:03:00', $this->timezone);
        
        $this->assertTrue(CronExpression::matches($expression, $match1));
        $this->assertTrue(CronExpression::matches($expression, $match2));
        $this->assertTrue(CronExpression::matches($expression, $match3));
        $this->assertFalse(CronExpression::matches($expression, $noMatch));
    }

    public function test_custom_cron_expression(): void
    {
        // Every day at 2:30 PM
        $expression = '30 14 * * *';
        
        $match = new DateTimeImmutable('2026-02-04 14:30:00', $this->timezone);
        $noMatch1 = new DateTimeImmutable('2026-02-04 14:31:00', $this->timezone);
        $noMatch2 = new DateTimeImmutable('2026-02-04 15:30:00', $this->timezone);
        
        $this->assertTrue(CronExpression::matches($expression, $match));
        $this->assertFalse(CronExpression::matches($expression, $noMatch1));
        $this->assertFalse(CronExpression::matches($expression, $noMatch2));
    }

    public function test_get_next_run_date_for_hourly(): void
    {
        $expression = CronExpression::hourly();
        
        $now = new DateTimeImmutable('2026-02-04 10:27:00', $this->timezone);
        $next = CronExpression::getNextRunDate($expression, $now);
        
        $this->assertEquals('2026-02-04 11:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_get_next_run_date_for_daily(): void
    {
        $expression = CronExpression::dailyAt(3, 0);
        
        $now = new DateTimeImmutable('2026-02-04 10:27:00', $this->timezone);
        $next = CronExpression::getNextRunDate($expression, $now);
        
        $this->assertEquals('2026-02-05 03:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_monthly_on_specific_day(): void
    {
        $expression = CronExpression::monthlyOn(15, 12, 0);
        
        $match = new DateTimeImmutable('2026-02-15 12:00:00', $this->timezone);
        $noMatch = new DateTimeImmutable('2026-02-16 12:00:00', $this->timezone);
        
        $this->assertTrue(CronExpression::matches($expression, $match));
        $this->assertFalse(CronExpression::matches($expression, $noMatch));
    }

    public function test_range_syntax(): void
    {
        // Weekdays (Monday-Friday) at 6 PM
        $expression = '0 18 * * 1-5';
        
        // 2026-02-09 is Monday
        $monday = new DateTimeImmutable('2026-02-09 18:00:00', $this->timezone);
        
        // 2026-02-13 is Friday
        $friday = new DateTimeImmutable('2026-02-13 18:00:00', $this->timezone);
        
        // 2026-02-14 is Saturday
        $saturday = new DateTimeImmutable('2026-02-14 18:00:00', $this->timezone);
        
        $this->assertTrue(CronExpression::matches($expression, $monday));
        $this->assertTrue(CronExpression::matches($expression, $friday));
        $this->assertFalse(CronExpression::matches($expression, $saturday));
    }

    public function test_list_syntax(): void
    {
        // Run at minutes 0, 15, 30, 45
        $expression = '0,15,30,45 * * * *';
        
        $match1 = new DateTimeImmutable('2026-02-04 10:00:00', $this->timezone);
        $match2 = new DateTimeImmutable('2026-02-04 10:15:00', $this->timezone);
        $match3 = new DateTimeImmutable('2026-02-04 10:30:00', $this->timezone);
        $match4 = new DateTimeImmutable('2026-02-04 10:45:00', $this->timezone);
        $noMatch = new DateTimeImmutable('2026-02-04 10:20:00', $this->timezone);
        
        $this->assertTrue(CronExpression::matches($expression, $match1));
        $this->assertTrue(CronExpression::matches($expression, $match2));
        $this->assertTrue(CronExpression::matches($expression, $match3));
        $this->assertTrue(CronExpression::matches($expression, $match4));
        $this->assertFalse(CronExpression::matches($expression, $noMatch));
    }
}
