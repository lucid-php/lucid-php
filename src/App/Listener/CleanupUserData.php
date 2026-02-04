<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\UserDeleted;

/**
 * Cleans up user-related data when user is deleted
 */
class CleanupUserData
{
    /**
     * Handle the UserDeleted event
     */
    public function handle(UserDeleted $event): void
    {
        // In real application, delete user files, sessions, tokens, etc.
        error_log(sprintf(
            "🗑️  Cleaning up data for deleted user: %s",
            $event->email
        ));
    }
}
