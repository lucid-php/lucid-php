<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Json Validation Rule
 * 
 * Validates that a string is valid JSON.
 * 
 * Philosophy: Explicit Over Convenient
 * - Uses PHP's json_validate() (PHP 8.3+)
 * - No automatic parsing or decoding
 * - Just validates the syntax
 * 
 * Example:
 * 
 * #[Json]
 * public string $metadata
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Json implements ValidatorRuleInterface
{
    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // PHP 8.3+ has json_validate()
        if (function_exists('json_validate')) {
            return json_validate($value);
        }

        // Fallback for PHP < 8.3
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function message(string $field): string
    {
        return "The field [{$field}] must be valid JSON.";
    }
}
