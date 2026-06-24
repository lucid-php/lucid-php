<?php

declare(strict_types=1);

namespace Core\Attribute;

use Attribute;

/**
 * Cache a controller method's response for $ttl seconds.
 *
 * Metadata only — enforced by the Router. SECURITY: the Router only caches
 * anonymous GET requests (no active session, no Authorization header) and only
 * 2xx responses, so per-user/authenticated responses are never shared.
 *
 *   #[Cache(ttl: 60)]
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class Cache
{
    public function __construct(
        public int $ttl = 60
    ) {
    }
}
