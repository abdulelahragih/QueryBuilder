<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Types\JoinType;

class JoinClause implements Clause
{
    public readonly Expression|string $table;
    public readonly ConditionsClause $conditionClauses;
    public readonly JoinType $joinType;

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
}
