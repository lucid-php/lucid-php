<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * AlphaNumeric Validation Rule
 * 
 * Validates that a string contains only alphanumeric characters.
 * 
 * Philosophy: Explicit Over Convenient
 * - Option to allow spaces
 * - Option to allow unicode
 * - Clear about what's allowed
 * 
 * Example:
 * 
 * #[AlphaNumeric]
 * public string $username
 * 
 * #[AlphaNumeric(allowSpaces: true)]
 * public string $displayName
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AlphaNumeric implements ValidatorRuleInterface
{
    public function __construct(
        private readonly bool $allowSpaces = false,
        private readonly bool $unicode = false
    ) {}

    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if ($this->unicode) {
            $pattern = $this->allowSpaces ? '/^[\p{L}\p{N}\s]+$/u' : '/^[\p{L}\p{N}]+$/u';
        } else {
            $pattern = $this->allowSpaces ? '/^[a-zA-Z0-9\s]+$/' : '/^[a-zA-Z0-9]+$/';
        }

        return preg_match($pattern, $value) === 1;
    }

    public function message(string $field): string
    {
        $allowed = 'letters and numbers' . ($this->allowSpaces ? ' and spaces' : '');
        return "The field [{$field}] must contain only {$allowed}.";
    }
}
