<?php

declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder;

use Abdulelahragih\QueryBuilder\Builders\JoinClauseBuilder;
use Abdulelahragih\QueryBuilder\Builders\WhereQueryBuilder;
use Abdulelahragih\QueryBuilder\Grammar\FromClause;
use Abdulelahragih\QueryBuilder\Grammar\JoinClause;
use Abdulelahragih\QueryBuilder\Grammar\LimitClause;
use Abdulelahragih\QueryBuilder\Grammar\OffsetClause;
use Abdulelahragih\QueryBuilder\Grammar\OrderByClause;
use Abdulelahragih\QueryBuilder\Grammar\SelectClause;
use Abdulelahragih\QueryBuilder\Helpers\BindingsManager;
use Abdulelahragih\QueryBuilder\Pagination\PaginatedResult;
use Abdulelahragih\QueryBuilder\Traits\CanPaginate;
use Abdulelahragih\QueryBuilder\Types\JoinType;
use Abdulelahragih\QueryBuilder\Types\OrderType;
use Closure;
use Exception;
use InvalidArgumentException;
use PDO;

class QueryBuilder
{
    use CanPaginate;

    private PDO $pdo;
    private string $table;
    private SelectClause $selectClause;
    private WhereQueryBuilder $whereQueryBuilder;
    private FromClause $fromClause;
    /**
     * @var JoinClause[]
     */
    private array $joinClauses = [];
    private LimitClause $limitClause;
    private OffsetClause $offsetClause;
    private OrderByClause $orderByClause;
    private BindingsManager $bindingsManager;
    private ?Closure $objConverter = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->bindingsManager = new BindingsManager();
        $this->whereQueryBuilder = new WhereQueryBuilder($this->bindingsManager);
    }

    /**
     * @throws Exception
     */
    public function get(): array
    {
        $query = $this->buildQuery();
        $statement = $this->pdo->prepare($query);
        if (!$statement->execute($this->bindingsManager->getBindings())) {
            return [];
        }
        $items = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (isset($this->objConverter)) {
            $items = array_map($this->objConverter, $items);
        }
        return $items;
    }

    /**
     * @throws Exception
     */
    public function paginate(int $page, int $limit): PaginatedResult
    {
        if (!isset($this->pdo)) {
            throw new Exception('You must specify a PDO instance');
        }

        $this->offsetClause = new OffsetClause(($page - 1) * $limit);
        if (!isset($this->limitClause)) {
            $this->limitClause = new LimitClause($limit);
        }
        $query = $this->buildPaginatedQuery();
        $statement = $this->pdo->prepare($query);
        if (!$statement->execute($this->bindingsManager->getBindings())) {
            return new PaginatedResult([], $this->getPaginationInfo(0, $page, $limit));
        }
        $items = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (isset($this->objConverter)) {
            $items = array_map($this->objConverter, $items);
        }
        $query = 'SELECT COUNT(*) FROM ' . $this->fromClause->table . ' ' . $this->getJoinClause() . ' ' . $this->getWhereClause();
        $statement = $this->pdo->prepare($query);
        $statement->execute($this->bindingsManager->getBindings());
        $total = (int)$statement->fetchColumn();
        return new PaginatedResult($items, $this->getPaginationInfo($total, $page, $limit));
    }

    public function toSql(): string
    {
        return $this->buildQuery();
    }

    private function buildQuery(): string
    {
        if (!isset($this->fromClause)) {
            throw new InvalidArgumentException('You must specify a table');
        }
        if (!isset($this->selectClause)) {
            $this->selectClause = new SelectClause();
        }
        return $this->selectClause->build() . $this->getFromClause() . $this->getJoinClause() .
            $this->getWhereClause() . $this->getOrderByClause() . $this->getLimitClause() .
            $this->getOffsetClause() . $this->queryEndMarker();
    }

    private function buildPaginatedQuery(): string
    {

        if (empty($this->fromClause)) {
            throw new InvalidArgumentException('You must specify a table');
        }
        if (!isset($this->selectClause)) {
            $this->selectClause = new SelectClause();
        }
        return $this->selectClause->build() . $this->getFromClause() .
            $this->getJoinClause() . $this->getWhereClause() . $this->getOrderByClause() .
            $this->getLimitClause() . $this->getOffsetClause() . $this->queryEndMarker();
    }

    public function table(string $table): self
    {
        $this->fromClause = new FromClause($table);
        return $this;
    }

    public function select(string ...$columns): self
    {
        $this->selectClause = new SelectClause($columns);
        return $this;
    }

    public function distinct(): self
    {
        if (!isset($this->selectClause)) {
            $this->selectClause = new SelectClause();
        }
        $this->selectClause->setDistinct(true);
        return $this;
    }

    public function orderBy(array $columns, string $type = 'ASC'): self
    {
        if (!OrderType::contains($type)) {
            throw new InvalidArgumentException('Invalid order type ' . $type);
        }
        $this->orderByClause = new OrderByClause($columns, OrderType::from($type));
        return $this;
    }

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
            throw new InvalidArgumentException('Invalid join type ' . $type);
        }
        $joinClauseBuilder = new JoinClauseBuilder($this->bindingsManager, $table, JoinType::from($type));
        $joinClauseBuilder->on($column1, $operator, $column2);
        $this->joinClauses[] = $joinClauseBuilder->build();
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

    private function getFromClause(): string
    {
        return ' ' . $this->fromClause->build();
    }

    public function getWhereClause(): string
    {
        return ' ' . $this->whereQueryBuilder->build();
    }

    private function getJoinClause(): string
    {
        $joinClauses = '';
        foreach ($this->joinClauses as $joinClause) {
            $joinClauses .= $joinClause->build() . "\n";
        }
        return ' ' . trim($joinClauses); // TODO optimize
    }

    private function getOrderByClause(): string
    {
        if (!isset($this->orderByClause)) {
            return '';
        }
        return ' ' . $this->orderByClause->build();
    }

    private function getLimitClause(): string
    {
        if (!isset($this->limitClause)) {
            return '';
        }
        return ' ' . $this->limitClause->build();
    }

    private function getOffsetClause(): string
    {
        if (!isset($this->offsetClause)) {
            return '';
        }
        return ' ' . $this->offsetClause->build();
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
}
