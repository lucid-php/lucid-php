-- Jobs Table for Queue System
-- Stores queued jobs for background processing

CREATE TABLE jobs (
    id TEXT PRIMARY KEY,
    queue TEXT NOT NULL DEFAULT 'default',
    payload TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL,
    -- Reservation columns: a popped job is marked reserved (not deleted) until
    -- the worker acknowledges it, so a crash mid-processing never loses a job.
    reserved_at INTEGER DEFAULT NULL,
    reservation_token TEXT DEFAULT NULL
);

-- Index for efficient job retrieval
CREATE INDEX idx_jobs_queue_available ON jobs(queue, available_at);
CREATE INDEX idx_jobs_reservation_token ON jobs(reservation_token);
