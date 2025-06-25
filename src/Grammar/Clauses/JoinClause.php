<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;
use Abdulelahragih\QueryBuilder\Types\JoinType;

class JoinClause implements Clause
{
    private Expression|string $table;
    private ConditionsClause $conditionClauses;
    private JoinType $joinType;

    /**
     * @param Expression|string $table
     * @param ConditionsClause $conditionClauses
     * @param JoinType $joinType
     */
    public function __construct(Expression|string $table, ConditionsClause $conditionClauses, JoinType $joinType = JoinType::Inner)
    {
        $this->table = $table;
        $this->conditionClauses = $conditionClauses;
        $this->joinType = $joinType;
    }


    /**
     * @return string
     */
    public function build(): string
    {
        return $this->joinType->value . ' JOIN ' . SqlUtils::quoteIdentifier($this->table) . ' ON ' . $this->conditionClauses->build();
    }
}
