<?php

declare(strict_types=1);

namespace App\Event;

/**
 * Event dispatched when a new user is created
 * 
 * Philosophy:
 * - Events are simple data containers
 * - Readonly for immutability
 * - No behavior, just data
 * - Type-safe properties
 */
readonly class UserCreated
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
    ) {}
}
