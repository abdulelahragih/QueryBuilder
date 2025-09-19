<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

use Abdulelahragih\QueryBuilder\Data\QueryBuilderException;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;

class DeleteStatement implements Statement
{
    /**
     * @param Expression|string $table
     * @param array|null $joinClause
     * @param WhereClause|null $whereClause
     * @param bool $forceDelete
     */
    public function __construct(
        private readonly Expression|string $table,
        private readonly ?array            $joinClause = null,
        private readonly ?WhereClause      $whereClause = null,
        private bool                       $forceDelete = false
    )
    {
    }

    public function setForceDelete(bool $forceUpdate): void
    {
        $this->forceDelete = $forceUpdate;
    }

    public function getTable(): Expression|string
    {
        return $this->table;
    }

    public function getJoinClauses(): ?array
    {
        return $this->joinClause;
    }

    public function getWhereClause(): ?WhereClause
    {
        return $this->whereClause;
    }

    /**
     * @throws QueryBuilderException
     */
    public function ensureSafe(): void
    {
        if (!isset($this->whereClause) || empty($this->whereClause->conditionClauses->getConditions())) {
            if (!$this->forceDelete) {
                throw new QueryBuilderException(
                    QueryBuilderException::DANGEROUS_QUERY,
                    'Delete statement without where clause is not allowed. Use force(true) to force delete.');
            }
        }
    }

    public function shouldForceDelete(): bool
    {
        return $this->forceDelete;
    }
}
