<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Numeric Validation Rule
 * 
 * Validates that a value is numeric (int, float, or numeric string).
 * 
 * Philosophy: Explicit Over Convenient
 * - Uses PHP's is_numeric() for standard behavior
 * - Accepts integers, floats, and numeric strings
 * - No automatic type casting
 * 
 * Example:
 * 
 * #[Numeric]
 * public string|int $amount
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Numeric implements ValidatorRuleInterface
{
    public function validate(mixed $value): bool
    {
        return is_numeric($value);
    }

    public function message(string $field): string
    {
        return "The field [{$field}] must be numeric.";
    }
}
