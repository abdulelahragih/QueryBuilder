<?php

namespace Abdulelahragih\QueryBuilder\Grammar;

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


    public function build(): string
    {
        $result = '(';
        $conditionsCount = count($this->conditions);
        for ($i = 0; $i < $conditionsCount; $i++) {
            $condition = $this->conditions[$i];
            if ($i == 0) {
                $result .= $condition->build();
            } else {
                $result .= $condition->conjunction->build() . ' ' . $condition->build();
            }
            if ($i != $conditionsCount - 1) {
                $result .= ' ';
            }
        }
        $result .= ')';
        return $result;
    }
}
