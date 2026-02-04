<?php

declare(strict_types=1);

namespace Core\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    public function __construct(
        public private(set) string $middlewareClass
    ) {}
}
