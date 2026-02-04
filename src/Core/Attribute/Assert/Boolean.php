<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Boolean Validation Rule
 * 
 * Validates that a value is boolean or boolean-like.
 * 
 * Philosophy: Explicit Over Convenient
 * - Accepts true/false, 1/0, "1"/"0", "true"/"false"
 * - Strict about what's considered boolean
 * - No magic type coercion
 * 
 * Example:
 * 
 * #[Boolean]
 * public bool|string $active
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Boolean implements ValidatorRuleInterface
{
    public function validate(mixed $value): bool
    {
        return in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false', 'TRUE', 'FALSE'], strict: true);
    }

    public function message(string $field): string
    {
        return "The field [{$field}] must be a boolean value.";
    }
}
