<?php

declare(strict_types=1);

namespace Core\Attribute;

use Attribute;

/**
 * RateLimit Attribute
 * 
 * Explicitly declares rate limiting rules for a route.
 * No hidden defaults, no magic.
 * 
 * Philosophy: Explicit over convenient.
 * - Want rate limiting? Declare it explicitly with numbers.
 * - Want no rate limit? Don't add the attribute.
 * 
 * @example
 * #[RateLimit(requests: 100, window: 60)]  // 100 requests per 60 seconds
 * #[RateLimit(requests: 10, window: 1)]    // 10 requests per second (strict)
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RateLimit
{
    /**
     * @param int $requests Maximum number of requests allowed
     * @param int $window Time window in seconds
     */
    public function __construct(
        public private(set) int $requests,
        public private(set) int $window,
    ) {
    }
}
