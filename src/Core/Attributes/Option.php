<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class Option
{
    public function __construct(
        public string $name,
        public string $shortcut = '',
        public string $description = '',
        public mixed $default = null
    ) {}
}
