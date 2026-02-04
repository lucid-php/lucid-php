<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\UserCreated;

/**
 * Logs user creation for audit trail
 */
class LogUserCreation
{
    /**
     * Handle the UserCreated event
     */
    public function handle(UserCreated $event): void
    {
        error_log(sprintf(
            "✓ User created: ID=%d, Name=%s, Email=%s",
            $event->userId,
            $event->name,
            $event->email
        ));
    }
}
