<?php

namespace Abdulelahragih\QueryBuilder;

use Closure;
use Exception;
use PDO;

class DBSingleton
{
    private static PDO $pdo;

    public function __construct(PDO $pdo)
    {
        self::$pdo = $pdo;
    }

    public static function init(PDO $pdo): self
    {
        return new self($pdo);
    }

    /**
     * @throws Exception
     */
    public static function table(string $table): QueryBuilder
    {
        if (!isset(self::$pdo)) {
            throw new Exception('Database connection not initialized. Please call DB::init() first.');
        }
        return (new QueryBuilder(self::$pdo))->table($table);
    }

    public static function getPdo(): PDO
    {
        return self::$pdo;
    }

    public static function beginTransaction(): void
    {
        self::$pdo->beginTransaction();
    }

    public static function commit(): void
    {
        self::$pdo->commit();
    }

    public static function rollBack(): void
    {
        self::$pdo->rollBack();
    }

    public static function inTransaction(): bool
    {
        return self::$pdo->inTransaction();
    }


    /**
     * @throws Exception
     */
    public static function transaction(Closure $transaction): void
    {
        self::beginTransaction();
        try {
            $transaction();
            self::commit();
        } catch (Exception $e) {
            self::rollBack();
            throw $e;
        }
    }

    public static function lastInsertId(): string
    {
        return self::$pdo->lastInsertId();
    }

}