<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Integer Validation Rule
 * 
 * Validates that a value is an integer or integer string.
 * 
 * Philosophy: Explicit Over Convenient
 * - Checks for integer values
 * - Accepts numeric strings that represent integers
 * - No floating point values
 * 
 * Example:
 * 
 * #[Integer]
 * public string|int $count
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Integer implements ValidatorRuleInterface
{
    public function validate(mixed $value): bool
    {
        if (is_int($value)) {
            return true;
        }

        if (is_string($value) && ctype_digit($value)) {
            return true;
        }

        if (is_string($value) && $value[0] === '-' && ctype_digit(substr($value, 1))) {
            return true;
        }

        return false;
    }

    public function message(string $field): string
    {
        return "The field [{$field}] must be an integer.";
    }
}
