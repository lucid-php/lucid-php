<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Required implements ValidatorRuleInterface
{
    public function validate(mixed $value): bool
    {
        return !empty($value) || $value === 0 || $value === '0';
    }

    public function message(string $field): string
    {
        return "The field [{$field}] is required.";
    }
}
