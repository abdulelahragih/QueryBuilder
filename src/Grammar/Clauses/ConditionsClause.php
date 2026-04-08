<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

class ConditionsClause implements Clause
{
    /**
     * @var (Condition|ConditionsGroup)[]
     */
    private array $conditions;

    /**
     * @param Condition|ConditionsGroup ...$conditions
     */
    public function __construct(Condition|ConditionsGroup ...$conditions)
    {
        $this->conditions = $conditions;
    }

    public function addCondition(Condition|ConditionsGroup $condition): void
    {
        $this->conditions[] = $condition;
    }

    public function mergeWith(ConditionsClause $conditionsClause): void
    {
        $this->conditions = array_merge($this->conditions, $conditionsClause->conditions);
    }

    /**
     * @return (Condition|ConditionsGroup)[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

}
