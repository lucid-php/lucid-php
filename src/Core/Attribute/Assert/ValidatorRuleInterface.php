<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

interface ValidatorRuleInterface
{
    public function validate(mixed $value): bool;
    public function message(string $field): string;
}
