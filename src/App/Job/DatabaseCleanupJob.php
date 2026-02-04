<?php

declare(strict_types=1);

namespace App\Job;

use Core\Schedule\CronExpression;
use Core\Schedule\ScheduledJobInterface;
use Core\Database\Database;
use Core\Mail\MailerInterface;
use Core\Mail\Mail;
use Core\Log\Logger;
use Core\Log\LogLevel;

/**
 * Example: Database Cleanup Job
 * 
 * Demonstrates a scheduled job with multiple dependencies:
 * - Database for querying and cleanup
 * - Mailer for sending notifications
 * - Logger for tracking execution
 * 
 * Runs weekly on Sunday at 2 AM
 */
class DatabaseCleanupJob implements ScheduledJobInterface
{
    public function __construct(
        private readonly Database $db,
        private readonly MailerInterface $mailer,
        private readonly Logger $logger
    ) {}

    public function schedule(): string
    {
        // Every Sunday at 2 AM
        return CronExpression::weeklyOn(0, 2, 0);
    }

    public function execute(): void
    {
        $this->logger->log(LogLevel::INFO, 'Starting database cleanup job');

        try {
            // 1. Delete expired tokens (older than 7 days)
            $deletedTokens = $this->db->execute(
                "DELETE FROM tokens WHERE expires_at < datetime('now', '-7 days')"
            );

            // 2. Clean up old failed jobs (older than 30 days)
            $deletedJobs = $this->db->execute(
                "DELETE FROM jobs WHERE status = 'failed' AND created_at < datetime('now', '-30 days')"
            );

            // 3. Log statistics
            $this->logger->log(
                LogLevel::INFO,
                'Database cleanup completed',
                [
                    'deleted_tokens' => $deletedTokens,
                    'deleted_jobs' => $deletedJobs,
                ]
            );

            // 4. Send notification email if significant cleanup occurred
            if ($deletedTokens > 0 || $deletedJobs > 0) {
                $this->mailer->send(
                    Mail::create(
                        to: 'admin@example.com',
                        subject: 'Database Cleanup Report',
                        body: "Weekly database cleanup completed.\n\n" .
                              "Deleted tokens: {$deletedTokens}\n" .
                              "Deleted jobs: {$deletedJobs}"
                    )
                );
            }

        } catch (\Throwable $e) {
            $this->logger->log(
                LogLevel::ERROR,
                'Database cleanup job failed',
                ['error' => $e->getMessage()]
            );

            // Send error notification
            $this->mailer->send(
                Mail::create(
                    to: 'admin@example.com',
                    subject: 'Database Cleanup Failed',
                    body: "Error: {$e->getMessage()}"
                )
            );

            throw $e; // Re-throw to mark job as failed
        }
    }

    public function getDescription(): string
    {
        return 'Clean up expired database records (weekly)';
    }
}
