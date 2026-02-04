<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Url Validation Rule
 * 
 * Validates that a string is a valid URL.
 * 
 * Philosophy: Explicit Over Convenient
 * - Uses PHP's FILTER_VALIDATE_URL
 * - No custom URL parsing magic
 * - Can optionally require specific schemes
 * 
 * Example:
 * 
 * #[Url]
 * public string $website
 * 
 * #[Url(schemes: ['https'])]
 * public string $secureUrl
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Url implements ValidatorRuleInterface
{
    /**
     * @param string[] $schemes Optional allowed schemes (e.g., ['http', 'https'])
     */
    public function __construct(
        private readonly array $schemes = []
    ) {}

    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        // If schemes specified, validate against them
        if (!empty($this->schemes)) {
            $parsed = parse_url($value);
            $scheme = $parsed['scheme'] ?? '';
            
            return in_array($scheme, $this->schemes, true);
        }

        return true;
    }

    public function message(string $field): string
    {
        if (!empty($this->schemes)) {
            $schemes = implode(', ', $this->schemes);
            return "The field [{$field}] must be a valid URL with scheme: {$schemes}.";
        }

        return "The field [{$field}] must be a valid URL.";
    }
}
