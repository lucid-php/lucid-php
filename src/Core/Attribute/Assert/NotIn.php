<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * NotIn Validation Rule
 * 
 * Validates that a value is NOT in a forbidden list (blacklist).
 * 
 * Philosophy: Explicit Over Convenient
 * - Explicitly list all forbidden values
 * - Uses strict comparison (===)
 * - No type coercion
 * 
 * Example:
 * 
 * #[NotIn(['admin', 'root', 'superuser'])]
 * public string $username
 * 
 * #[NotIn([0, -1])]
 * public int $userId
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NotIn implements ValidatorRuleInterface
{
    /**
     * @param array<mixed> $forbidden
     */
    public function __construct(
        private readonly array $forbidden
    ) {}

    public function validate(mixed $value): bool
    {
        return !in_array($value, $this->forbidden, strict: true);
    }

    public function message(string $field): string
    {
        return "The field [{$field}] contains a forbidden value.";
    }
}
