<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Types\JoinType;

class JoinClause implements Clause
{
    private string $table;
    private ConditionsClause $conditionClauses;
    private JoinType $joinType;

    /**
     * @param string $table
     * @param ConditionsClause $conditionClauses
     * @param JoinType $joinType
     */
    public function __construct(string $table, ConditionsClause $conditionClauses, JoinType $joinType = JoinType::Inner)
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
        return $this->joinType->value . ' JOIN ' . $this->table . ' ON ' . $this->conditionClauses->build();
    }
}
