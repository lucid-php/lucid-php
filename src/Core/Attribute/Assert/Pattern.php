<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Pattern Validation Rule
 * 
 * Validates that a string matches a regular expression pattern.
 * 
 * Philosophy: Explicit Over Convenient
 * - No predefined patterns (write the regex explicitly)
 * - No magic pattern names
 * - Full control over validation logic
 * 
 * Example:
 * 
 * #[Pattern('/^[A-Z][a-z]+$/')]
 * public string $firstName
 * 
 * #[Pattern('/^\d{4}-\d{2}-\d{2}$/')]
 * public string $date
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Pattern implements ValidatorRuleInterface
{
    public function __construct(
        private readonly string $pattern,
        private readonly string $customMessage = ''
    ) {}

    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match($this->pattern, $value) === 1;
    }

    public function message(string $field): string
    {
        if ($this->customMessage !== '') {
            return $this->customMessage;
        }

        return "The field [{$field}] format is invalid.";
    }
}
