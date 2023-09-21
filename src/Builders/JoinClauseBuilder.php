<?php

namespace Abdulelahragih\QueryBuilder\Builders;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\Condition;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\ConditionsClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\ConditionsGroup;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\Conjunction;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\JoinClause;
use Abdulelahragih\QueryBuilder\Helpers\BindingsManager;
use Abdulelahragih\QueryBuilder\Types\JoinType;
use Closure;
use InvalidArgumentException;

class JoinClauseBuilder
{

    private ConditionsClause $conditionsClause;

    public function __construct(
        private readonly BindingsManager $bindingsManager,
        private readonly string          $table,
        private readonly JoinType        $joinType = JoinType::Inner
    ) {
        $this->conditionsClause = new ConditionsClause();
    }

    public function build(): JoinClause
    {
        return new JoinClause($this->table, $this->conditionsClause, $this->joinType);
    }

    public function where(
        string|Closure             $column,
        ?string                    $operator = null,
        string|int|float|bool|null $value = null,
        bool                       $and = true
    ): self {
        if ($column instanceof Closure) {
            $this->subJoin($column, $and);
            return $this;
        }
        if (!in_array($operator ?? '', ['=', '!=', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException('Invalid operator ' . $operator);
        }
        $placeholder = $this->bindingsManager->add($value);
        $condition = new Condition($column, $operator, $placeholder, $and ? Conjunction::AND() : Conjunction::OR());

        $this->conditionsClause->addCondition($condition);
        return $this;
    }

    private function subJoin(Closure $closure, bool $and = true): void
    {
        $builder = new JoinClauseBuilder($this->bindingsManager, $this->table, $this->joinType);
        $closure($builder);
        $this->conditionsClause->addCondition(
            new ConditionsGroup(
                $builder->conditionsClause->getConditions(),
                $and ? Conjunction::AND() : Conjunction::OR()
            )
        );
    }

    public function orWhere(
        mixed   $column,
        ?string $operator = null,
        ?string $value = null
    ): self {
        $this->where($column, $operator, $value, false);
        return $this;
    }

    public function on(
        string $column1,
        string $operator,
        string $column2,
    ): self {
        if (!in_array($operator, ['=', '!=', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException('Invalid operator ' . $operator);
        }
        $condition = new Condition($column1, $operator, $column2, Conjunction::AND());
        $this->conditionsClause->addCondition($condition);
        return $this;
    }

    public function orOn(
        string $column1,
        string $operator,
        string $column2,
    ): self {
        if (!in_array($operator, ['=', '!=', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException('Invalid operator ' . $operator);
        }
        $condition = new Condition($column1, $operator, $column2, Conjunction::OR());
        $this->conditionsClause->addCondition($condition);
        return $this;
    }
}
