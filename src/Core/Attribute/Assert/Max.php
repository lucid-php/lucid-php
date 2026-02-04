<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * Max Validation Rule
 * 
 * Validates that a numeric value does not exceed the specified maximum.
 * 
 * Philosophy: Explicit Over Convenient
 * - Works only with numeric values (int/float)
 * - Clear error message specifying the maximum
 * - No type coercion magic
 * 
 * Example:
 * 
 * #[Max(100)]
 * public int $score
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Max implements ValidatorRuleInterface
{
    public function __construct(
        private readonly int|float $max
    ) {}

    public function validate(mixed $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        return $value <= $this->max;
    }

    public function message(string $field): string
    {
        return "The field [{$field}] must not exceed {$this->max}.";
    }
}
