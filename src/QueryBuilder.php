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
    }

    /**
     * @throws QueryBuilderException
     */
    public function get(): Collection
    {
        $query = $this->buildQuery();
        $statement = $this->pdo->prepare($query);
        if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
            throw new QueryBuilderException(QueryBuilderException::EXECUTE_ERROR, 'Error executing the query');
        }
        $items = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (isset($this->objConverter)) {
            $items = array_map($this->objConverter, $items);
        }
        $this->resetBuilderState();
        return Collection::make($items);
    }

    public function paginate(int $page, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
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
        $this->resetBuilderState();
        return (new LengthAwarePaginator($items, $page, $perPage, $total))->setPageName($pageName);
    }

    public function simplePaginate(int $page, int $perPage, string $pageName = 'page'): SimplePaginator
    {
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
        $this->resetBuilderState();
        return (new SimplePaginator($items, $page, $perPage))->setPageName($pageName);
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
            $this->orderByClause
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

    public function table(string $table): self
    {
        $this->fromClause = new FromClause($table);
        return $this;
    }

    public function select(string ...$columns): self
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
        $query = $updateStatement->build() . $this->queryEndMarker();
        $resultedSql = $query;
        $statement = $this->pdo->prepare($query);
        if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
            return null;
        }
        $this->resetBuilderState();
        return $statement->rowCount();
    }

    public function delete(?string &$resultedSql = null): ?int
    {
        $deleteStatement = new DeleteStatement(
            $this->fromClause->table,
            $this->joinClauses,
            $this->whereQueryBuilder->getWhereClause()
        );
        $query = $deleteStatement->build() . $this->queryEndMarker();
        $resultedSql = $query;
        $statement = $this->pdo->prepare($query);
        if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
            return null;
        }
        $this->resetBuilderState();
        return $statement->rowCount();
    }

    /**
     * @param array $columnsToValues an associative array of columns to values to be inserted
     * @returns int the number of inserted rows or null on failure
     */
    public function insert(array $columnsToValues, ?string &$resultedSql = null): ?int
    {
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

        $insertStatement = new InsertStatement(
            $this->fromClause->table,
            $columns,
            $values
        );
        $query = $insertStatement->build() . $this->queryEndMarker();
        $resultedSql = $query;
        $statement = $this->pdo->prepare($query);
        if (!$statement->execute($this->bindingsManager->getBindingsOrNull())) {
            return null;
        }
        $this->resetBuilderState();
        return $statement->rowCount();
    }

    public function distinct(): self
    {
        if (!isset($this->selectClause)) {
            $this->selectClause = new SelectStatement();
        }
        $this->selectClause->setDistinct(true);
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function orderBy(string $column, string $type = 'ASC'): self
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

    public function orderByDesc(string $column): self
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
        string         $table,
        string|Closure $column1,
        ?string        $operator = null,
        ?string        $column2 = null,
        string         $type = 'INNER'
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
        string         $table,
        string|Closure $column1,
        ?string        $operator = null,
        ?string        $column2 = null,
    ): self
    {
        $this->join($table, $column1, $operator, $column2, 'LEFT');
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function rightJoin(
        string         $table,
        string|Closure $column1,
        ?string        $operator = null,
        ?string        $column2 = null,
    ): self
    {
        $this->join($table, $column1, $operator, $column2, 'RIGHT');
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function fullJoin(
        string         $table,
        string|Closure $column1,
        ?string        $operator = null,
        ?string        $column2 = null,
    ): self
    {
        $this->join($table, $column1, $operator, $column2, 'FULL');
        return $this;
    }

    public function where(
        string|Closure             $column,
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
        string                $column,
        string|int|float|bool $value,
        bool                  $and = true
    ): self
    {
        $this->whereQueryBuilder->whereLike($column, $value, $and);
        return $this;
    }

    public function whereNotLike(
        string                $column,
        string|int|float|bool $value,
        bool                  $and = true
    ): self
    {
        $this->whereQueryBuilder->whereNotLike($column, $value, $and);
        return $this;
    }

    public function whereIn(
        string $column,
        array  $values,
        bool   $and = true
    ): self
    {
        $this->whereQueryBuilder->whereIn($column, $values, $and);
        return $this;
    }

    public function whereNotIn(
        string $column,
        array  $values,
        bool   $and = true
    ): self
    {
        $this->whereQueryBuilder->whereNotIn($column, $values, $and);
        return $this;
    }

    public function whereNull(
        string $column,
        bool   $and = true
    ): self
    {
        $this->whereQueryBuilder->whereNull($column, $and);
        return $this;
    }

    public function whereNotNull(
        string $column,
        bool   $and = true
    ): self
    {
        $this->whereQueryBuilder->whereNotNull($column, $and);
        return $this;
    }

    public function whereBetween(
        string                $column,
        string|int|float|bool $value1,
        string|int|float|bool $value2,
        bool                  $and = true
    ): self
    {
        $this->whereQueryBuilder->whereBetween($column, $value1, $value2, $and);
        return $this;
    }

    public function whereNotBetween(
        string                $column,
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

    public function objectConverter(Closure $objConverter): self
    {
        $this->objConverter = $objConverter;
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function first(string ...$columns): mixed
    {
        if (!isset($this->selectClause)) {
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
     * @throws QueryBuilderException
     */
    public function pluck(string $column): array
    {
        $this->select($column);
        $result = $this->get();
        return $result->pluck($column);
    }

    public function getValues(): array
    {
        return Collection::make($this->bindingsManager->getBindings())->values()->toArray();
    }
}
