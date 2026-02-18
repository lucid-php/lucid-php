<?php

declare(strict_types=1);

namespace Core\Schedule;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * CronExpression - Parse and evaluate cron expressions
 * 
 * Supports standard 5-part cron syntax:
 * * * * * *
 * | | | | |
 * | | | | +---- Day of week (0-7, Sunday=0 or 7)
 * | | | +------ Month (1-12)
 * | | +-------- Day of month (1-31)
 * | +---------- Hour (0-23)
 * +------------ Minute (0-59)
 * 
 * No magic. Simple, explicit cron parsing.
 */
class CronExpression
{
    private const MINUTE = 0;
    private const HOUR = 1;
    private const DAY = 2;
    private const MONTH = 3;
    private const WEEKDAY = 4;

    /**
     * Check if a cron expression matches a given time
     */
    public static function matches(string $expression, DateTimeImmutable $time): bool
    {
        $parts = self::parse($expression);

        return self::matchesPart($parts[self::MINUTE], (int) $time->format('i'))
            && self::matchesPart($parts[self::HOUR], (int) $time->format('H'))
            && self::matchesPart($parts[self::DAY], (int) $time->format('d'))
            && self::matchesPart($parts[self::MONTH], (int) $time->format('n'))
            && self::matchesPart($parts[self::WEEKDAY], (int) $time->format('w'));
    }

    /**
     * Get next run date for a cron expression
     */
    public static function getNextRunDate(string $expression, DateTimeImmutable $from): DateTimeImmutable
    {
        $parts = self::parse($expression);
        $current = $from->modify('+1 minute')->setTime(
            (int) $from->format('H'),
            (int) $from->format('i'),
            0
        );

        // Try up to 366 days to find next match
        for ($i = 0; $i < 525600; $i++) {
            if (self::matches($expression, $current)) {
                return $current;
            }
            $current = $current->modify('+1 minute');
        }

        throw new InvalidArgumentException("Cannot find next run date for expression: {$expression}");
    }

    /**
     * Parse cron expression into parts
     * 
     * @return array<int, array<int>>
     */
    private static function parse(string $expression): array
    {
        $parts = preg_split('/\s+/', trim($expression));

        if (count($parts) !== 5) {
            throw new InvalidArgumentException(
                "Invalid cron expression: {$expression}. Expected 5 parts (minute hour day month weekday)"
            );
        }

        return [
            self::MINUTE => self::parsePart($parts[0], 0, 59),
            self::HOUR => self::parsePart($parts[1], 0, 23),
            self::DAY => self::parsePart($parts[2], 1, 31),
            self::MONTH => self::parsePart($parts[3], 1, 12),
            self::WEEKDAY => self::parsePart($parts[4], 0, 7), // Both 0 and 7 = Sunday
        ];
    }

    /**
     * Parse a single cron part
     * 
     * Supports:
     * - * (any)
     * - 5 (specific value)
     * - 1-5 (range)
     * - star/5 (step values)
     * - 1,3,5 (list)
     * 
     * @return array<int>
     */
    private static function parsePart(string $part, int $min, int $max): array
    {
        // Wildcard
        if ($part === '*') {
            return range($min, $max);
        }

        // Step values (*/5)
        if (str_contains($part, '*/')) {
            $step = (int) explode('/', $part)[1];
            if ($step <= 0) {
                throw new \InvalidArgumentException("Step value must be positive, got: {$step}");
            }
            $values = [];
            for ($i = $min; $i <= $max; $i += $step) {
                $values[] = $i;
            }
            return $values;
        }

        // Lists (1,3,5)
        if (str_contains($part, ',')) {
            $values = array_map('intval', explode(',', $part));
            foreach ($values as $value) {
                if ($value < $min || $value > $max) {
                    throw new \InvalidArgumentException("Value {$value} out of range [{$min}, {$max}]");
                }
            }
            return $values;
        }

        // Ranges (1-5)
        if (str_contains($part, '-')) {
            [$start, $end] = array_map('intval', explode('-', $part));
            if ($start > $end) {
                throw new \InvalidArgumentException("Invalid range: {$start}-{$end} (start must be <= end)");
            }
            if ($start < $min || $end > $max) {
                throw new \InvalidArgumentException("Range [{$start}, {$end}] out of bounds [{$min}, {$max}]");
            }
            return range($start, $end);
        }

        // Single value
        $value = (int) $part;
        if ($value < $min || $value > $max) {
            throw new \InvalidArgumentException("Value {$value} out of range [{$min}, {$max}]");
        }
        return [$value];
    }

    /**
     * Check if a value matches a cron part
     * 
     * @param array<int> $allowedValues
     */
    private static function matchesPart(array $allowedValues, int $value): bool
    {
        // Handle Sunday as both 0 and 7
        if ($value === 0 && in_array(7, $allowedValues, true)) {
            return true;
        }
        if ($value === 7 && in_array(0, $allowedValues, true)) {
            return true;
        }

        return in_array($value, $allowedValues, true);
    }

    /**
     * Predefined cron expressions
     */
    public static function everyMinute(): string
    {
        return '* * * * *';
    }

    public static function everyFiveMinutes(): string
    {
        return '*/5 * * * *';
    }

    public static function everyTenMinutes(): string
    {
        return '*/10 * * * *';
    }

    public static function everyFifteenMinutes(): string
    {
        return '*/15 * * * *';
    }

    public static function everyThirtyMinutes(): string
    {
        return '*/30 * * * *';
    }

    public static function hourly(): string
    {
        return '0 * * * *';
    }

    public static function hourlyAt(int $minute): string
    {
        if ($minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException("Minute must be between 0 and 59, got: {$minute}");
        }
        return "{$minute} * * * *";
    }

    public static function daily(): string
    {
        return '0 0 * * *';
    }

    public static function dailyAt(int $hour, int $minute = 0): string
    {
        if ($hour < 0 || $hour > 23) {
            throw new \InvalidArgumentException("Hour must be between 0 and 23, got: {$hour}");
        }
        if ($minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException("Minute must be between 0 and 59, got: {$minute}");
        }
        return "{$minute} {$hour} * * *";
    }

    public static function weekly(): string
    {
        return '0 0 * * 0'; // Sunday at midnight
    }

    public static function weeklyOn(int $dayOfWeek, int $hour = 0, int $minute = 0): string
    {
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new \InvalidArgumentException("Day of week must be between 0 and 6, got: {$dayOfWeek}");
        }
        if ($hour < 0 || $hour > 23) {
            throw new \InvalidArgumentException("Hour must be between 0 and 23, got: {$hour}");
        }
        if ($minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException("Minute must be between 0 and 59, got: {$minute}");
        }
        return "{$minute} {$hour} * * {$dayOfWeek}";
    }

    public static function monthly(): string
    {
        return '0 0 1 * *'; // First day of month
    }

    public static function monthlyOn(int $day, int $hour = 0, int $minute = 0): string
    {
        if ($day < 1 || $day > 31) {
            throw new \InvalidArgumentException("Day must be between 1 and 31, got: {$day}");
        }
        if ($hour < 0 || $hour > 23) {
            throw new \InvalidArgumentException("Hour must be between 0 and 23, got: {$hour}");
        }
        if ($minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException("Minute must be between 0 and 59, got: {$minute}");
        }
        return "{$minute} {$hour} {$day} * *";
    }
}
