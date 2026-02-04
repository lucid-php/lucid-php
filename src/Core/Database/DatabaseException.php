<?php

declare(strict_types=1);

namespace Core\Database;

use Exception;

/**
 * Database Exception
 * 
 * Thrown for database operation failures including transactions.
 * Provides static factory methods for common error scenarios.
 * 
 * Philosophy:
 * - Explicit exception types via factory methods
 * - Clear error messages
 * - No magic - just typed exceptions
 */
class DatabaseException extends Exception
{
    /**
     * Transaction already active (can't nest)
     */
    public static function transactionAlreadyActive(): self
    {
        return new self('Cannot start transaction: transaction already active. Nested transactions not supported.');
    }

    /**
     * Failed to start transaction
     */
    public static function transactionStartFailed(): self
    {
        return new self('Failed to start database transaction.');
    }

    /**
     * No active transaction
     */
    public static function noActiveTransaction(string $operation): self
    {
        return new self("Cannot {$operation}: no active transaction.");
    }

    /**
     * Failed to commit transaction
     */
    public static function transactionCommitFailed(): self
    {
        return new self('Failed to commit database transaction.');
    }

    /**
     * Failed to rollback transaction
     */
    public static function transactionRollbackFailed(): self
    {
        return new self('Failed to rollback database transaction.');
    }

    /**
     * Query execution failed
     */
    public static function queryFailed(string $sql, string $error): self
    {
        return new self("Query failed: {$error}\nSQL: {$sql}");
    }

    /**
     * Connection failed
     */
    public static function connectionFailed(string $error): self
    {
        return new self("Database connection failed: {$error}");
    }
}
