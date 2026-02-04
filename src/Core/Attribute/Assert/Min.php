<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Min Validation Rule
 * 
 * Validates that a numeric value is at least the specified minimum.
 * 
 * Philosophy: Explicit Over Convenient
 * - Works only with numeric values (int/float)
 * - Clear error message specifying the minimum
 * - No type coercion magic
 * 
 * Example:
 * 
 * #[Min(18)]
 * public int $age
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Min implements ValidatorRuleInterface
{
    public function __construct(
        private readonly int|float $min
    ) {}

    public function validate(mixed $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        return $value >= $this->min;
    }

    public function message(string $field): string
    {
        return "The field [{$field}] must be at least {$this->min}.";
    }
}
