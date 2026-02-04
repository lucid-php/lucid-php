<?php

declare(strict_types=1);

namespace Core\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RoutePrefix
{
    public function __construct(
        public private(set) string $prefix
    ) {
    }
}
