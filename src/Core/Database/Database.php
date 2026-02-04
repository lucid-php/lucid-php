<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;
use PDOException;

class Database
{
    private PDO $pdo;

    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = []
    ) {
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options + $defaultOptions);
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function execute(string $sql, array $params = []): bool
    {
        return $this->pdo->prepare($sql)->execute($params);
    }
    
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    public function getDriverName(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Begin a database transaction
     * 
     * Explicit transaction start. Must be paired with commit() or rollback().
     * Philosophy: No auto-commit, no hidden behavior.
     * 
     * @throws DatabaseException if transaction cannot be started
     */
    public function beginTransaction(): void
    {
        if ($this->pdo->inTransaction()) {
            throw DatabaseException::transactionAlreadyActive();
        }

        if (!$this->pdo->beginTransaction()) {
            throw DatabaseException::transactionStartFailed();
        }
    }

    /**
     * Commit the current transaction
     * 
     * Makes all changes permanent. Explicit commit required.
     * 
     * @throws DatabaseException if not in transaction or commit fails
     */
    public function commit(): void
    {
        if (!$this->pdo->inTransaction()) {
            throw DatabaseException::noActiveTransaction('commit');
        }

        if (!$this->pdo->commit()) {
            throw DatabaseException::transactionCommitFailed();
        }
    }

    /**
     * Rollback the current transaction
     * 
     * Discards all changes. Call on error/exception.
     * 
     * @throws DatabaseException if not in transaction or rollback fails
     */
    public function rollback(): void
    {
        if (!$this->pdo->inTransaction()) {
            throw DatabaseException::noActiveTransaction('rollback');
        }

        if (!$this->pdo->rollBack()) {
            throw DatabaseException::transactionRollbackFailed();
        }
    }

    /**
     * Check if currently in a transaction
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Execute a callback within a transaction
     * 
     * Automatically handles commit/rollback based on callback success/failure.
     * Throws exception on failure (after rollback).
     * 
     * Philosophy-compliant because:
     * - Explicit method call (transaction(...))
     * - Callback makes the transactional code visible
     * - Exception propagation is explicit
     * 
     * @template T
     * @param callable(): T $callback
     * @return T
     * @throws \Throwable if callback fails (after rollback)
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
}
