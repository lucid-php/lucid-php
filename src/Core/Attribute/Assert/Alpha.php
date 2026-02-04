<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Alpha Validation Rule
 * 
 * Validates that a string contains only alphabetic characters.
 * 
 * Philosophy: Explicit Over Convenient
 * - Option to allow spaces
 * - Option to allow unicode letters
 * - No hidden character sets
 * 
 * Example:
 * 
 * #[Alpha]
 * public string $firstName
 * 
 * #[Alpha(allowSpaces: true)]
 * public string $fullName
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Alpha implements ValidatorRuleInterface
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
            $pattern = $this->allowSpaces ? '/^[\p{L}\s]+$/u' : '/^[\p{L}]+$/u';
        } else {
            $pattern = $this->allowSpaces ? '/^[a-zA-Z\s]+$/' : '/^[a-zA-Z]+$/';
        }

        return preg_match($pattern, $value) === 1;
    }

    public function message(string $field): string
    {
        $allowed = 'letters' . ($this->allowSpaces ? ' and spaces' : '');
        return "The field [{$field}] must contain only {$allowed}.";
    }
}
