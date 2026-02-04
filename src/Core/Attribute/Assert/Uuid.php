<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Uuid Validation Rule
 * 
 * Validates that a string is a valid UUID (v4 by default).
 * 
 * Philosophy: Explicit Over Convenient
 * - Can validate specific UUID versions
 * - Uses explicit regex patterns
 * - No hidden format assumptions
 * 
 * Example:
 * 
 * #[Uuid]
 * public string $id
 * 
 * #[Uuid(version: 4)]
 * public string $transactionId
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Uuid implements ValidatorRuleInterface
{
    public function __construct(
        private readonly ?int $version = null
    ) {}

    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if ($this->version === null) {
            // Any UUID version
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        } else {
            // Specific UUID version
            $pattern = sprintf(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-%d[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $this->version
            );
        }

        return preg_match($pattern, $value) === 1;
    }

    public function message(string $field): string
    {
        $version = $this->version ? " (version {$this->version})" : '';
        return "The field [{$field}] must be a valid UUID{$version}.";
    }
}
