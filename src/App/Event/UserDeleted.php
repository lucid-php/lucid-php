<?php

declare(strict_types=1);

namespace App\Event;

/**
 * Event dispatched when a user is deleted
 */
readonly class UserDeleted
{
    public function __construct(
        public int $userId,
        public string $email,
    ) {}
}
