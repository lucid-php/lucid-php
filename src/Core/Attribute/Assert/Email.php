<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Email implements ValidatorRuleInterface
{
    public function validate(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function message(string $field): string
    {
        return "The field [{$field}] must be a valid email address.";
    }
}
