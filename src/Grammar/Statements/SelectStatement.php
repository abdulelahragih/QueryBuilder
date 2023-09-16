<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\Clause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\FromClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\JoinClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\LimitClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OffsetClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OrderByClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;

class SelectStatement implements Statement
{

    private bool $distinct = false;

    /**
     * @param FromClause|null $fromClause
     * @param array|null $columns
     * @param JoinClause[] $joinClause
     * @param WhereClause|null $whereClause
     * @param LimitClause|null $limitClause
     * @param OffsetClause|null $offsetClause
     * @param OrderByClause|null $orderByClause
     */
    public function __construct(
        private readonly ?FromClause    $fromClause = null,
        public readonly ?array          $columns = null,
        private readonly ?array         $joinClause = null,
        private readonly ?WhereClause   $whereClause = null,
        private readonly ?LimitClause   $limitClause = null,
        private readonly ?OffsetClause  $offsetClause = null,
        private readonly ?OrderByClause $orderByClause = null,
    )
    {
    }

    public function setDistinct(bool $distinct): void
    {
        $this->distinct = $distinct;
    }

    /**
     * @return bool
     */
    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    public function build(): string
    {
        return $this->buildSelectQuery();
    }

    private function buildSelectQuery(): string
    {
        $distinct = $this->distinct ? 'DISTINCT ' : '';
        return "SELECT " . $distinct . (empty($this->columns) ? '*' : implode(', ', $this->columns)) .
            $this->buildOrEmpty($this->fromClause) .
            $this->buildOrEmpty($this->joinClause) .
            $this->buildOrEmpty($this->whereClause) .
            $this->buildOrEmpty($this->limitClause) .
            $this->buildOrEmpty($this->offsetClause) .
            $this->buildOrEmpty($this->orderByClause);
    }

    private function buildOrEmpty(null|Clause|array $clause): string
    {
        if (is_null($clause)) {
            return '';
        }

        if (is_array($clause)) {
            if (empty($clause)) {
                return '';
            }

            $builtClause = '';
            foreach ($clause as $item) {
                $builtClause .= $item->build() . "\n";
            }
            return ' ' . trim($builtClause);
        }
        $builtClause = $clause->build();
        return empty($builtClause) ? '' : ' ' . $builtClause;
    }
}