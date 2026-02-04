<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\UserCreated;
use App\Job\SendWelcomeEmailJob;
use Core\Queue\QueueInterface;

/**
 * Sends welcome email when user is created
 * 
 * Philosophy:
 * - Dispatches job to queue instead of blocking
 * - Queue resolved from container
 * - Explicit job creation and dispatching
 */
class SendWelcomeEmail
{
    public function __construct(
        private final QueueInterface $queue
    ) {}

    /**
     * Handle the UserCreated event by queuing email job
     */
    public function handle(UserCreated $event): void
    {
        // Dispatch job to queue - doesn't block HTTP response
        $this->queue->push(new SendWelcomeEmailJob(
            userId: $event->userId,
            name: $event->name,
            email: $event->email
        ));
    }
}
