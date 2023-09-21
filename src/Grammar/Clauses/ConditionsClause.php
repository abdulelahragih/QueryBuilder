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

    public function build(): string
    {
        $result = '';
        $conditionsCount = count($this->conditions);
        for ($i = 0; $i < $conditionsCount; $i++) {
            $conditionsGroup = $this->conditions[$i];
            if ($i == 0) {
                $result .= $conditionsGroup->build();
            } else {
                $result .= $conditionsGroup->conjunction->build() . ' ' . $conditionsGroup->build();
            }
            if ($i != $conditionsCount - 1) {
                $result .= ' ';
            }
        }
        return $result;
    }
}
