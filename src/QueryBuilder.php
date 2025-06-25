<?php

declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder;

use Abdulelahragih\QueryBuilder\Builders\JoinClauseBuilder;
use Abdulelahragih\QueryBuilder\Builders\WhereQueryBuilder;
use Abdulelahragih\QueryBuilder\Data\Collection;
use Abdulelahragih\QueryBuilder\Data\QueryBuilderException;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\FromClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\JoinClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\LimitClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OffsetClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OrderByClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Grammar\Statements\DeleteStatement;
use Abdulelahragih\QueryBuilder\Grammar\Statements\InsertStatement;
use Abdulelahragih\QueryBuilder\Grammar\Statements\SelectStatement;
use Abdulelahragih\QueryBuilder\Grammar\Statements\UpdateStatement;
use Abdulelahragih\QueryBuilder\Helpers\BindingsManager;
use Abdulelahragih\QueryBuilder\Pagination\LengthAwarePaginator;
use Abdulelahragih\QueryBuilder\Pagination\SimplePaginator;
use Abdulelahragih\QueryBuilder\Types\JoinType;
use Abdulelahragih\QueryBuilder\Types\OrderType;
use Closure;
use InvalidArgumentException;
use PDO;

class QueryBuilder
{

    private PDO $pdo;
    private array $columns = [];
    private ?SelectStatement $selectClause = null;
    private WhereQueryBuilder $whereQueryBuilder;
    private ?FromClause $fromClause = null;
    /**
     * @var JoinClause[]
     */
    private array $joinClauses = [];
    private ?LimitClause $limitClause = null;
    private ?OffsetClause $offsetClause = null;
    private ?OrderByClause $orderByClause = null;
    private BindingsManager $bindingsManager;
    private ?Closure $objConverter = null;
    private bool $isDistinct = false;
    private bool $forceExecution = false;
    private ?array $returningColumns = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->bindingsManager = new BindingsManager();
        $this->whereQueryBuilder = new WhereQueryBuilder($this->bindingsManager);
    }

    private function resetBuilderState(): void
    {
        $this->bindingsManager->reset();
        $this->selectClause = null;
        $this->bindingsManager = new BindingsManager();
        $this->whereQueryBuilder = new WhereQueryBuilder($this->bindingsManager);
        $this->fromClause = null;
        $this->joinClauses = [];
        $this->limitClause = null;
        $this->offsetClause = null;
        $this->orderByClause = null;
        $this->objConverter = null;
        $this->forceExecution = false;
        $this->returningColumns = null;
    }

    /**
     * @throws QueryBuilderException
     */
    public function get(): Collection
    {
        try {
            $query = $this->buildQuery();
            $statement = $this->pdo->prepare($query);
            if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
                throw new QueryBuilderException(QueryBuilderException::EXECUTE_ERROR, 'Error executing the query');
            }
            $items = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (isset($this->objConverter)) {
                $items = array_map($this->objConverter, $items);
            }
            return Collection::make($items);
        } finally {
            $this->resetBuilderState();
        }
    }

    public function paginate(int $page, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        try {
            $this->offsetClause = new OffsetClause(($page - 1) * $perPage);
            if (!isset($this->limitClause)) {
                $this->limitClause = new LimitClause($perPage);
            }
            $query = $this->buildPaginatedQuery();
            $statement = $this->pdo->prepare($query);
            if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
                return LengthAwarePaginator::empty($perPage);
            }
            $items = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (isset($this->objConverter)) {
                $items = array_map($this->objConverter, $items);
            }
            $query = "SELECT COUNT(*) FROM {$this->fromClause->table} {$this->getJoinClause()} {$this->getWhereClause()}";
            $statement = $this->pdo->prepare($query);
            $statement->execute($this->bindingsManager->getBindingsOrNull());
            $total = (int)$statement->fetchColumn();
            return (new LengthAwarePaginator($items, $page, $perPage, $total))->setPageName($pageName);
        } finally {
            $this->resetBuilderState();
        }
    }

    public function simplePaginate(int $page, int $perPage, string $pageName = 'page'): SimplePaginator
    {
        try {
            $this->offsetClause = new OffsetClause(($page - 1) * $perPage);
            if (!isset($this->limitClause)) {
                $this->limitClause = new LimitClause($perPage);
            }
            $query = $this->buildPaginatedQuery();
            $statement = $this->pdo->prepare($query);
            if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
                return SimplePaginator::empty($perPage);
            }
            $items = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (isset($this->objConverter)) {
                $items = array_map($this->objConverter, $items);
            }
            return (new SimplePaginator($items, $page, $perPage))->setPageName($pageName);
        } finally {
            $this->resetBuilderState();
        }
    }

    /**
     * @throws QueryBuilderException
     */
    public function toSql(): string
    {
        return $this->buildQuery();
    }

    /**
     * @throws QueryBuilderException
     */
    private function buildQuery(): string
    {
        if (!isset($this->fromClause)) {
            throw new QueryBuilderException(QueryBuilderException::MISSING_TABLE, 'You must specify a table');
        }
        if (!isset($this->selectClause)) {
            $this->selectClause = $this->createSelectStatement();
        }
        return $this->selectClause->build() . $this->queryEndMarker();
    }

    private function createSelectStatement(): SelectStatement
    {
        return new SelectStatement(
            $this->fromClause,
            $this->columns,
            $this->joinClauses,
            $this->whereQueryBuilder->getWhereClause(),
            $this->limitClause,
            $this->offsetClause,
            $this->orderByClause,
            $this->isDistinct
        );
    }

    private function buildPaginatedQuery(): string
    {
        if (empty($this->fromClause)) {
            throw new InvalidArgumentException('You must specify a table');
        }
        if (!isset($this->selectClause)) {
            $this->selectClause = $this->createSelectStatement();
        }
        return $this->selectClause->build() . $this->queryEndMarker();
    }

    public function table(Expression|string $table): self
    {
        $this->fromClause = new FromClause($table);
        return $this;
    }

    public function select(Expression|string ...$columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @param array $columnsToValues an associative array of columns to values to be updated
     * @returns int the number of affected rows or null on failure
     * @throws QueryBuilderException
     */
    public function update(array $columnsToValues, ?string &$resultedSql = null): ?int
    {
        try {
            // convert values to place holder
            $columnsToValues = array_map(function ($value) {
                if (is_array($value)) {
                    throw new QueryBuilderException(QueryBuilderException::INVALID_QUERY, 'Value cannot be an array');
                }
                return $this->bindingsManager->add($value);
            }, $columnsToValues);
            $updateStatement = new UpdateStatement(
                $this->fromClause->table,
                $columnsToValues,
                $this->joinClauses,
                $this->whereQueryBuilder->getWhereClause()
            );
            $updateStatement->setForceUpdate($this->forceExecution);
            if ($this->returningColumns !== null) {
                if (!$this->supportsReturning()) {
                    throw new QueryBuilderException(
                        QueryBuilderException::UNSUPPORTED_FEATURE,
                        'RETURNING is not supported by the current driver'
                    );
                }
                $updateStatement->setReturning($this->returningColumns);
            }
            $query = $updateStatement->build() . $this->queryEndMarker();
            $resultedSql = $query;
            $statement = $this->pdo->prepare($query);
            if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
                return null;
            }
            return $statement->rowCount();
        } finally {
            $this->resetBuilderState();
        }
    }

    public function delete(?string &$resultedSql = null): ?int
    {
        try {
            $deleteStatement = new DeleteStatement(
                $this->fromClause->table,
                $this->joinClauses,
                $this->whereQueryBuilder->getWhereClause()
            );
            $deleteStatement->setForceDelete($this->forceExecution);
            $query = $deleteStatement->build() . $this->queryEndMarker();
            $resultedSql = $query;
            $statement = $this->pdo->prepare($query);
            if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
                return null;
            }
            return $statement->rowCount();
        } finally {
            $this->resetBuilderState();
        }
    }

    /**
     * @param array $columnsToValues an associative array of columns to values to be inserted
     * @param array|null $updateOnDuplicate
     * @param string|null $resultedSql
     * @return int|null the number of inserted rows or null on failure
     */
    public function upsert(array $columnsToValues, ?array $updateOnDuplicate = [], ?string &$resultedSql = null): ?int
    {
        try {
            $this->bindingsManager->reset();
            if (!empty($columnsToValues) && is_array(reset($columnsToValues))) {
                // $columnsToValues is an array of arrays (multiple rows)
                $columns = array_keys($columnsToValues[0]);
                $values = array_map(function ($row) {
                    // convert values to placeholders
                    return array_map(function ($value) {
                        return $this->bindingsManager->add($value);
                    }, $row);
                }, $columnsToValues);
            } else {
                // $columnsToValues is a single array (single row)
                $columns = array_keys($columnsToValues);
                $values = array_map(function ($value) {
                    return $this->bindingsManager->add($value);
                }, $columnsToValues);
            }

            if (!empty($updateOnDuplicate)) {
                foreach ($updateOnDuplicate as $column => $value) {
                    if (is_int($column)) {
                        continue;
                    }
                    $updateOnDuplicate[$column] = $this->bindingsManager->add($value);
                }
            } elseif ($updateOnDuplicate === []) {
                foreach ($columnsToValues as $column => $value) {
                    $updateOnDuplicate[$column] = $this->bindingsManager->add($value);
                }
            }

            $insertStatement = new InsertStatement(
                $this->fromClause->table,
                $columns,
                $values,
                $updateOnDuplicate
            );
            if ($this->returningColumns !== null) {
                if (!$this->supportsReturning()) {
                    throw new QueryBuilderException(
                        QueryBuilderException::UNSUPPORTED_FEATURE,
                        'RETURNING is not supported by the current driver'
                    );
                }
                $insertStatement->setReturning($this->returningColumns);
            }
            $query = $insertStatement->build() . $this->queryEndMarker();
            $resultedSql = $query;
            $statement = $this->pdo->prepare($query);
            if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
                return null;
            }
            return $statement->rowCount();

        } finally {
            $this->resetBuilderState();
        }
    }

    /**
     * @param array $columnsToValues an associative array of columns to values to be inserted
     * @param string|null $resultedSql
     * @return int|null the number of inserted rows or null on failure
     */
    public function insert(array $columnsToValues, ?string &$resultedSql = null): ?int
    {
        return $this->upsert($columnsToValues, null, $resultedSql);
    }

    public function distinct(): self
    {
        $this->isDistinct = true;
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function orderBy(Expression|string $column, string $type = 'ASC'): self
    {
        if (!OrderType::contains($type)) {
            throw new QueryBuilderException(QueryBuilderException::INVALID_ORDER_TYPE, 'Invalid order type ' . $type);
        }
        if (!isset($this->orderByClause)) {
            $this->orderByClause = new OrderByClause();
        }
        $this->orderByClause->addColumn($column, OrderType::from($type));
        return $this;
    }

    public function orderByDesc(Expression|string $column): self
    {
        if (!isset($this->orderByClause)) {
            $this->orderByClause = new OrderByClause();
        }
        $this->orderByClause->addColumn($column, OrderType::Descending);
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function join(
        Expression|string         $table,
        Expression|string|Closure $column1,
        ?string                   $operator = null,
        Expression|string|null    $column2 = null,
        string                    $type = 'INNER'
    ): self
    {
        if ($column1 instanceof Closure) {
            $joinClauseBuilder = new JoinClauseBuilder($this->bindingsManager, $table, JoinType::from($type));
            $column1($joinClauseBuilder);
            $this->joinClauses[] = $joinClauseBuilder->build();
            return $this;
        }
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT', 'FULL'], true)) {
            throw new QueryBuilderException(QueryBuilderException::INVALID_JOIN_TYPE, 'Invalid join type ' . $type);
        }
        $joinClauseBuilder = new JoinClauseBuilder($this->bindingsManager, $table, JoinType::from($type));
        $joinClauseBuilder->on($column1, $operator, $column2);
        $this->joinClauses[] = $joinClauseBuilder->build();
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function leftJoin(
        Expression|string         $table,
        Expression|string|Closure $column1,
        ?string                   $operator = null,
        Expression|string|null    $column2 = null,
    ): self
    {
        $this->join($table, $column1, $operator, $column2, 'LEFT');
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function rightJoin(
        Expression|string         $table,
        Expression|string|Closure $column1,
        ?string                   $operator = null,
        Expression|string|null    $column2 = null,
    ): self
    {
        $this->join($table, $column1, $operator, $column2, 'RIGHT');
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function fullJoin(
        Expression|string         $table,
        Expression|string|Closure $column1,
        ?string                   $operator = null,
        Expression|string|null    $column2 = null,
    ): self
    {
        $this->join($table, $column1, $operator, $column2, 'FULL');
        return $this;
    }

    public function where(
        Expression|string|Closure  $column,
        ?string                    $operator = null,
        string|int|float|bool|null $value = null,
        bool                       $and = true
    ): self
    {
        $this->whereQueryBuilder->where($column, $operator, $value, $and);
        return $this;
    }

    public function orWhere(
        mixed                      $column,
        ?string                    $operator = null,
        string|int|float|bool|null $value = null
    ): self
    {
        $this->where($column, $operator, $value, false);
        return $this;
    }

    public function whereLike(
        Expression|string     $column,
        string|int|float|bool $value,
        bool                  $and = true
    ): self
    {
        $this->whereQueryBuilder->whereLike($column, $value, $and);
        return $this;
    }

    public function whereNotLike(
        Expression|string     $column,
        string|int|float|bool $value,
        bool                  $and = true
    ): self
    {
        $this->whereQueryBuilder->whereNotLike($column, $value, $and);
        return $this;
    }

    public function whereIn(
        Expression|string $column,
        array             $values,
        bool              $and = true
    ): self
    {
        $this->whereQueryBuilder->whereIn($column, $values, $and);
        return $this;
    }

    public function whereNotIn(
        Expression|string $column,
        array             $values,
        bool              $and = true
    ): self
    {
        $this->whereQueryBuilder->whereNotIn($column, $values, $and);
        return $this;
    }

    public function whereNull(
        Expression|string $column,
        bool              $and = true
    ): self
    {
        $this->whereQueryBuilder->whereNull($column, $and);
        return $this;
    }

    public function whereNotNull(
        Expression|string $column,
        bool              $and = true
    ): self
    {
        $this->whereQueryBuilder->whereNotNull($column, $and);
        return $this;
    }

    public function whereBetween(
        Expression|string     $column,
        string|int|float|bool $value1,
        string|int|float|bool $value2,
        bool                  $and = true
    ): self
    {
        $this->whereQueryBuilder->whereBetween($column, $value1, $value2, $and);
        return $this;
    }

    public function whereNotBetween(
        Expression|string     $column,
        string|int|float|bool $value1,
        string|int|float|bool $value2,
        bool                  $and = true
    ): self
    {
        $this->whereQueryBuilder->whereNotBetween($column, $value1, $value2, $and);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitClause = new LimitClause($limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetClause = new OffsetClause($offset);
        return $this;
    }

    public function getWhereClause(): string
    {
        if ($this->whereQueryBuilder->isEmpty()) {
            return '';
        }
        return ' ' . $this->whereQueryBuilder->build();
    }

    private function getJoinClause(): string
    {
        if (empty($this->joinClauses)) {
            return '';
        }

        $joinClauses = '';
        foreach ($this->joinClauses as $joinClause) {
            $joinClauses .= $joinClause->build() . "\n";
        }
        return ' ' . trim($joinClauses);
    }

    private function queryEndMarker(): string
    {
        return ';';
    }

    /**
     * Allow for custom object conversion after fetching the results
     * @param Closure $objConverter
     * @return $this
     */
    public function objectConverter(Closure $objConverter): self
    {
        $this->objConverter = $objConverter;
        return $this;
    }

    public function force(bool $force = true): self
    {
        $this->forceExecution = $force;
        return $this;
    }

    public function returning(string ...$columns): self
    {
        $this->returningColumns = $columns;
        return $this;
    }

    private function supportsReturning(): bool
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $version = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            return stripos($version, 'mariadb') !== false;
        }
        return in_array($driver, ['sqlite', 'pgsql'], true);
    }

    /**
     * Limit the number of results to 1 and return the first result
     * @throws QueryBuilderException
     */
    public function first(string ...$columns): mixed
    {
        if (empty($this->columns)) {
            $this->select(...$columns);
        }

        $this->limit(1);
        $result = $this->get();
        if ($result->isEmpty()) {
            return null;
        }
        $first = $result->first();
        if (is_array($first)) {
            if (count($first) === 1) {
                return array_values($first)[0];
            }
            return $first;
        }
        return $first;
    }

    /**
     * Retrieve a single column from the result
     * @throws QueryBuilderException
     */
    public function pluck(string $column): array
    {
        $this->select($column);
        $result = $this->get();
        return $result->pluck($column);
    }

    /**
     * Retrieve all the bound values
     * @return array
     */
    public function getValues(): array
    {
        return Collection::make($this->bindingsManager->getBindings())->values()->toArray();
    }

    /**
     * Allow for raw expressions in the query
     * @param string $value
     * @return Expression
     */
    public function raw(string $value): Expression
    {
        return Expression::make($value);
    }

    public function subQuery(Closure $callback): Expression
    {
        $builder = new self($this->pdo);
        $callback($builder);
        $sql = rtrim($builder->toSql(), ';');
        return Expression::make('(' . $sql . ')');
    }
}
