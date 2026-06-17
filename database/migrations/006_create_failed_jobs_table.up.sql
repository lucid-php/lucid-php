-- Failed Jobs Table for Queue System
-- A job that exhausts its retries is moved here instead of being dropped,
-- so failures are durable and inspectable/retryable.

CREATE TABLE failed_jobs (
    id TEXT PRIMARY KEY,
    queue TEXT NOT NULL DEFAULT 'default',
    payload TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    exception TEXT NOT NULL,
    failed_at INTEGER NOT NULL
);

CREATE INDEX idx_failed_jobs_queue ON failed_jobs(queue);
