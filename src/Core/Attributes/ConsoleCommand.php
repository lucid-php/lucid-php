<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class ConsoleCommand
{
    public function __construct(
        public string $name,
        public string $description = ''
    ) {}
}
