<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Range Validation Rule
 * 
 * Validates that a numeric value is within the specified range (inclusive).
 * 
 * Philosophy: Explicit Over Convenient
 * - Combines Min and Max in one attribute
 * - Works only with numeric values
 * - Both bounds are inclusive
 * 
 * Example:
 * 
 * #[Range(0, 100)]
 * public int $percentage
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Range implements ValidatorRuleInterface
{
    public function __construct(
        private readonly int|float $min,
        private readonly int|float $max
    ) {}

    public function validate(mixed $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        return $value >= $this->min && $value <= $this->max;
    }

    public function message(string $field): string
    {
        return "The field [{$field}] must be between {$this->min} and {$this->max}.";
    }
}
