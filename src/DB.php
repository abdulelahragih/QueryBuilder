<?php

namespace Abdulelahragih\QueryBuilder;

use Abdulelahragih\QueryBuilder\Data\Collection;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\Dialect;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Closure;
use Exception;
use PDO;

class DB
{
    private PDO $pdo;
    private Dialect $dialect;

    public function __construct(PDO $pdo, ?Dialect $dialect = null)
    {
        $this->pdo = $pdo;
        $this->dialect = $dialect;
    }

    /**
     * @throws Exception
     */
    public function table(string $table): QueryBuilder
    {
        if (!isset($this->pdo)) {
            throw new Exception('Database connection not initialized. Please call DB::init() first.');
        }
        return (new QueryBuilder($this->pdo, $this->dialect))->table($table);
    }

    public function setDialect(Dialect $dialect): self
    {
        $this->dialect = $dialect;
        return $this;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }


    /**
     * @throws Exception
     */
    public function transaction(Closure $callback): void
    {
        $this->beginTransaction();
        try {
            $callback();
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function exec(string $query, ?array $params = null): ?Collection
    {
        $statement = $this->pdo->prepare($query);
        if (!$statement->execute($params)) {
            return null;
        }
        $items = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (isset($this->objConverter)) {
            $items = array_map($this->objConverter, $items);
        }
        return Collection::make($items);
    }

    public function raw(string $value): Expression
    {
        return Expression::make($value);
    }
}
