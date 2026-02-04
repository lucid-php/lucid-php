<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * Too Many Requests Exception (HTTP 429)
 * 
 * Thrown when rate limit is exceeded.
 * Includes Retry-After header via resetTime.
 */
class TooManyRequestsException extends HttpException
{
    public function __construct(
        string $message = 'Too Many Requests',
        private int $resetTime = 0,
    ) {
        $headers = [];
        
        // Add Retry-After header (seconds until reset)
        if ($this->resetTime > 0) {
            $retryAfter = max(0, $this->resetTime - time());
            $headers['Retry-After'] = (string)$retryAfter;
        }

        parent::__construct($message, 429, $headers);
    }

    public function getResetTime(): int
    {
        return $this->resetTime;
    }
}
