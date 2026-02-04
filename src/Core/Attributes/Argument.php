<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class Argument
{
    public function __construct(
        public string $name,
        public string $description = '',
        public bool $required = true
    ) {}
}
