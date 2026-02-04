<?php

declare(strict_types=1);

namespace Core\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public private(set) string $method,
        public private(set) string $path
    ) {
    }
}
