<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

use Abdulelahragih\QueryBuilder\Data\QueryBuilderException;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;
use Abdulelahragih\QueryBuilder\Traits\CanBuildClause;

class DeleteStatement implements Statement
{

    use CanBuildClause;

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

    /**
     * @param bool $forceUpdate
     */
    public function setForceDelete(bool $forceUpdate): void
    {
        $this->forceDelete = $forceUpdate;
    }

    /**
     * @throws QueryBuilderException
     */
    public function build(): string
    {
        if (!isset($this->whereClause) || empty($this->whereClause->conditionClauses->getConditions())) {
            if (!$this->forceDelete) {
                throw new QueryBuilderException(
                    QueryBuilderException::DANGEROUS_QUERY,
                    'Delete statement without where clause is not allowed. Use force(true) to force delete.');
            }
        }
        return 'DELETE FROM ' . SqlUtils::quoteIdentifier($this->table) .
            $this->buildOrEmpty($this->joinClause) .
            $this->buildOrEmpty($this->whereClause);
    }
}