<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

class WhereClause implements Clause
{
    public readonly ConditionsClause $conditionClauses;

    public function __construct(?ConditionsClause $conditionClauses = null)
    {
        $this->conditionClauses = $conditionClauses ?? new ConditionsClause();
    }

    public function addConditionClause(ConditionsClause $conditionClauses): void
    {
        $this->conditionClauses->mergeWith($conditionClauses);
    }

    public function addCondition(Condition $condition): void
    {
        $this->conditionClauses->addCondition($condition);
    }

    public function addConditionsGroup(ConditionsGroup $conditionsGroup): void
    {
        $this->conditionClauses->addCondition($conditionsGroup);
    }

    public function build(): string
    {
        if (empty($this->conditionClauses->getConditions())) {
            return '';
        }
        return 'WHERE ' . $this->conditionClauses->build();
    }
}
