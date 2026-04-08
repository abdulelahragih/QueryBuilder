<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

use Abdulelahragih\QueryBuilder\Data\QueryBuilderException;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
class UpdateStatement implements Statement
{
    /**
     * @param Expression|string $table
     * @param array|null $columnsToValues
     * @param array|null $joinClause
     * @param WhereClause|null $whereClause
     * @param bool $forceUpdate
     */
    public function __construct(
        private readonly Expression|string $table,
        public readonly ?array             $columnsToValues = null,
        private readonly ?array            $joinClause = null,
        private readonly ?WhereClause      $whereClause = null,
        private bool                       $forceUpdate = false
    )
    {
    }

    /**
     * @param bool $forceUpdate
     */
    public function setForceUpdate(bool $forceUpdate): void
    {
        $this->forceUpdate = $forceUpdate;
    }

    public function getTable(): Expression|string
    {
        return $this->table;
    }

    public function getColumnsToValues(): ?array
    {
        return $this->columnsToValues;
    }

    public function getJoinClauses(): ?array
    {
        return $this->joinClause;
    }

    public function getWhereClause(): ?WhereClause
    {
        return $this->whereClause;
    }

    public function shouldForceUpdate(): bool
    {
        return $this->forceUpdate;
    }

    /**
     * @throws QueryBuilderException
     */
    public function ensureSafe(): void
    {
        if (!isset($this->whereClause) || empty($this->whereClause->conditionClauses->getConditions())) {
            if (!$this->forceUpdate) {
                throw new QueryBuilderException(
                    QueryBuilderException::DANGEROUS_QUERY,
                    'Update statement without where clause is not allowed. Use force(true) to force update.');
            }
        }
    }
}
