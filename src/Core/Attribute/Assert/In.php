<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

/**
 * In Validation Rule
 * 
 * Validates that a value is in an allowed list (whitelist).
 * 
 * Philosophy: Explicit Over Convenient
 * - Explicitly list all allowed values
 * - Uses strict comparison (===)
 * - No type coercion
 * 
 * Example:
 * 
 * #[In(['draft', 'published', 'archived'])]
 * public string $status
 * 
 * #[In([1, 2, 3, 5, 10])]
 * public int $priority
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class In implements ValidatorRuleInterface
{
    /**
     * @param array<mixed> $allowed
     */
    public function __construct(
        private readonly array $allowed
    ) {}

    public function validate(mixed $value): bool
    {
        return in_array($value, $this->allowed, strict: true);
    }

    public function message(string $field): string
    {
        $options = implode(', ', array_map(
            fn($v) => is_string($v) ? "'{$v}'" : (string)$v,
            $this->allowed
        ));
        
        return "The field [{$field}] must be one of: {$options}.";
    }
}
