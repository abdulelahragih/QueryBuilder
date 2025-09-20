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
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OnConflictClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OrderByClause;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\Dialect;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\MySqlDialect;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\PostgresDialect;
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
    private Dialect $dialect;
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
    private ?array $onConflictConfig = null;

    public function __construct(PDO $pdo, ?Dialect $dialect = null)
    {
        $this->pdo = $pdo;
        $this->dialect = $dialect ?? $this->detectDialect($pdo);
        $this->bindingsManager = new BindingsManager();
        $this->whereQueryBuilder = new WhereQueryBuilder($this->bindingsManager);
    }

    public function setDialect(Dialect $dialect): self
    {
        $this->dialect = $dialect;
        return $this;
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
        $this->onConflictConfig = null;
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
            $countSelect = new SelectStatement(
                $this->fromClause,
                [Expression::make('COUNT(*)')],
                $this->joinClauses,
                $this->whereQueryBuilder->getWhereClause()
            );
            $query = $this->dialect->compileSelect($countSelect);
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
        return $this->dialect->compileSelect($this->selectClause) . $this->queryEndMarker();
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
        return $this->dialect->compileSelect($this->selectClause) . $this->queryEndMarker();
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
            $query = $this->dialect->compileUpdate($updateStatement) . $this->queryEndMarker();
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
            $query = $this->dialect->compileDelete($deleteStatement) . $this->queryEndMarker();
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
     * @param array $uniqueBy
     * @param array|null $updateOnDuplicate
     * @param string|null $resultedSql
     * @return int|null the number of inserted rows or null on failure
     */
    public function upsert(array $columnsToValues, array $uniqueBy, ?array $updateOnDuplicate = [], ?string &$resultedSql = null): ?int
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
            if (is_null($this->onConflictConfig)) {
                $this->onConflictDoUpdate($uniqueBy, $updateOnDuplicate);
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

            $onConflictClause = $this->buildOnConflictClause($updateOnDuplicate);

            $insertStatement = new InsertStatement(
                $this->fromClause->table,
                $columns,
                $values,
                $updateOnDuplicate,
                $onConflictClause
            );
            $query = $this->dialect->compileInsert($insertStatement) . $this->queryEndMarker();
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
        $updateOnDuplicate = null;
        if ($this->onConflictConfig !== null && $this->onConflictConfig['doNothing'] === false) {
            $updateOnDuplicate = [];
        }

        return $this->upsert($columnsToValues, $updateOnDuplicate, $resultedSql);
    }

    public function distinct(): self
    {
        $this->isDistinct = true;
        return $this;
    }

    public function onConflictDoNothing(array|string $columns): self
    {
        $this->onConflictConfig = [
            'columns' => $this->normalizeConflictColumns($columns),
            'assignments' => null,
            'doNothing' => true,
        ];

        return $this;
    }

    public function onConflictDoUpdate(array|string $columns, ?array $assignments = []): self
    {
        $assignments ??= [];
        $this->onConflictConfig = [
            'columns' => $this->normalizeConflictColumns($columns),
            'assignments' => $assignments,
            'doNothing' => false,
        ];

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
        $whereClause = $this->whereQueryBuilder->getWhereClause();
        if (empty($whereClause->conditionClauses->getConditions())) {
            return '';
        }

        return ' ' . $this->dialect->compileWhereClause($whereClause);
    }

    /**
     * @param array|string $columns
     * @return array<int, Expression|string>
     */
    private function normalizeConflictColumns(array|string $columns): array
    {
        $columns = is_array($columns) ? $columns : [$columns];
        if (empty($columns)) {
            throw new InvalidArgumentException('On conflict requires at least one target column.');
        }

        foreach ($columns as $column) {
            if (!$column instanceof Expression && !is_string($column)) {
                throw new InvalidArgumentException('On conflict columns must be strings or Expression instances.');
            }
        }

        return $columns;
    }

    private function buildOnConflictClause(?array $updateOnDuplicate): ?OnConflictClause
    {
        if ($this->onConflictConfig === null) {
            return null;
        }

        $columns = $this->onConflictConfig['columns'];
        $assignmentsConfig = $this->onConflictConfig['assignments'];
        $doNothing = $this->onConflictConfig['doNothing'];

        if ($doNothing) {
            return new OnConflictClause($columns, null);
        }

        if ($assignmentsConfig === []) {
            if (empty($updateOnDuplicate)) {
                throw new InvalidArgumentException('Unable to infer on conflict assignments; provide explicit assignments.');
            }
            $assignments = $this->filterDefaultConflictAssignments($updateOnDuplicate, $columns);
            return new OnConflictClause($columns, $assignments);
        }

        $assignments = [];
        foreach ($assignmentsConfig as $column => $value) {
            if (is_int($column)) {
                $assignments[$value] = "EXCLUDED.$value";
                continue;
            }

            if ($value instanceof Expression) {
                $assignments[$column] = $value;
                continue;
            }

            if (is_string($value) && str_starts_with($value, ':')) {
                $assignments[$column] = $value;
                continue;
            }

            $assignments[$column] = $this->bindingsManager->add($value);
        }

        if (empty($assignments)) {
            throw new InvalidArgumentException('On conflict update requires at least one assignment.');
        }

        return new OnConflictClause($columns, $assignments);
    }

    private function detectDialect(PDO $pdo): Dialect
    {
        $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        return $driverName === 'pgsql' ? new PostgresDialect() : new MySqlDialect();
    }

    public function getDialect(): Dialect
    {
        return $this->dialect;
    }

    /**
     * @param array<string, Expression|string> $assignments
     * @param array<int, Expression|string> $conflictColumns
     * @return array<string, Expression|string>
     */
    private function filterDefaultConflictAssignments(array $assignments, array $conflictColumns): array
    {
        $columnNames = array_filter($conflictColumns, static fn($column) => is_string($column));
        if (empty($columnNames)) {
            return $assignments;
        }

        $filtered = array_diff_key($assignments, array_flip($columnNames));

        return empty($filtered) ? $assignments : $filtered;
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
}
