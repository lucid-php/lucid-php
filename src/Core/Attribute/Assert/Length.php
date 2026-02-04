<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Length implements ValidatorRuleInterface
{
    public function __construct(
        private int $min = 0, 
        private int $max = PHP_INT_MAX
    ) {}

    public function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $len = strlen($value);
        return $len >= $this->min && $len <= $this->max;
    }

    public function message(string $field): string
    {
        return "The field [{$field}] must be between {$this->min} and {$this->max} characters.";
    }
}
