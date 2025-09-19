<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

class ConditionsGroup implements Clause
{
    public readonly Conjunction $conjunction;

    /**
     * @param Condition[]|ConditionsGroup[] $conditions
     * @param Conjunction|null $conjunction
     */
    public function __construct(public readonly array $conditions = [], ?Conjunction $conjunction = null)
    {
        $this->conjunction = $conjunction ?? Conjunction::AND();
    }

}
