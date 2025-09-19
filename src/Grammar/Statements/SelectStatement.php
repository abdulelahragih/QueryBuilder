<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\FromClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\JoinClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\LimitClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OffsetClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OrderByClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;
class SelectStatement implements Statement
{
    /**
     * @param FromClause|null $fromClause
     * @param array|null $columns
     * @param JoinClause[] $joinClause
     * @param WhereClause|null $whereClause
     * @param LimitClause|null $limitClause
     * @param OffsetClause|null $offsetClause
     * @param OrderByClause|null $orderByClause
     * @param bool $distinct
     */
    public function __construct(
        private readonly ?FromClause    $fromClause = null,
        public readonly ?array          $columns = null,
        private readonly ?array         $joinClause = null,
        private readonly ?WhereClause   $whereClause = null,
        private readonly ?LimitClause   $limitClause = null,
        private readonly ?OffsetClause  $offsetClause = null,
        private readonly ?OrderByClause $orderByClause = null,
        private readonly bool           $distinct = false
    )
    {
    }

    /**
     * @return bool
     */
    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    public function getFromClause(): ?FromClause
    {
        return $this->fromClause;
    }

    /**
     * @return JoinClause[]|null
     */
    public function getJoinClauses(): ?array
    {
        return $this->joinClause;
    }

    public function getWhereClause(): ?WhereClause
    {
        return $this->whereClause;
    }

    public function getLimitClause(): ?LimitClause
    {
        return $this->limitClause;
    }

    public function getOffsetClause(): ?OffsetClause
    {
        return $this->offsetClause;
    }

    public function getOrderByClause(): ?OrderByClause
    {
        return $this->orderByClause;
    }
}
