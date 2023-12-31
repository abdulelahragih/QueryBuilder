<?php

namespace Abdulelahragih\QueryBuilder\Builders;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\Condition;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\ConditionsGroup;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\Conjunction;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;
use Abdulelahragih\QueryBuilder\Helpers\BindingsManager;
use Closure;
use InvalidArgumentException;

class WhereQueryBuilder
{
    private readonly WhereClause $whereClause;

    public function __construct(private readonly BindingsManager $bindingsManager)
    {
        $this->whereClause = new WhereClause();
    }

    // private api
    public function __call(string $name, array $arguments)
    {
        if ($name === 'getWhereClause') {
            return $this->whereClause;
        }
        throw new InvalidArgumentException('Method ' . $name . ' does not exist');
    }

    public function isEmpty(): bool
    {
        return empty($this->whereClause->conditionClauses->getConditions());
    }

    public function build(): string
    {
        return $this->whereClause->build();
    }

    public function where(
        mixed                      $column,
        ?string                    $operator = null,
        string|int|float|bool|null $value = null,
        bool                       $and = true
    ): self
    {
        if ($column instanceof Closure) {
            $this->whereNested($column, $and);
            return $this;
        }
        if (!in_array($operator ?? '', ['=', '!=', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException('Invalid operator ' . $operator);
        }
        $placeholder = $this->bindingsManager->add($value);
        $condition = new Condition($column, $operator, $placeholder, $and ? Conjunction::AND() : Conjunction::OR());

        $this->whereClause->addCondition($condition);
        return $this;
    }

    private function whereNested(Closure $closure, bool $and = true): void
    {
        $builder = new WhereQueryBuilder($this->bindingsManager);
        $closure($builder);
        $this->whereClause->addConditionsGroup(new ConditionsGroup(
            $builder->whereClause->conditionClauses->getConditions(),
            $and ? Conjunction::AND() : Conjunction::OR()
        ));
    }

    public function orWhere(
        mixed                      $column,
        ?string                    $operator = null,
        string|int|float|bool|null $value = null
    ): self
    {
        $this->where($column, $operator, $value, false);
        return $this;
    }

    public function whereLike(
        string                $column,
        string|int|float|bool $value,
        bool                  $and = true
    ): self
    {
        $placeholder = $this->bindingsManager->add($value);
        $condition = new Condition($column, 'LIKE', $placeholder, $and ? Conjunction::AND() : Conjunction::OR());
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereNotLike(
        string                $column,
        string|int|float|bool $value,
        bool                  $and = true
    ): self
    {
        $placeholder = $this->bindingsManager->add($value);
        $condition = new Condition($column, 'NOT LIKE', $placeholder, $and ? Conjunction::AND() : Conjunction::OR());
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereIn(
        string $column,
        array  $values,
        bool   $and = true
    ): self
    {
        if (empty($values)) {
            $this->addEmptyWhereIn($column, $values, $and);
            return $this;
        }
        $placeholders = [];
        foreach ($values as $value) {
            $placeholder = $this->bindingsManager->add($value);
            $placeholders[] = $placeholder;
        }
        $condition = new Condition(
            $column,
            'IN',
            '(' . implode(', ', $placeholders) . ')',
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
        return $this;
    }

    private function addEmptyWhereIn(
        string $column,
        array  $values,
        bool   $and = true
    ): void
    {
        $condition = new Condition(
            1,
            '=',
            0,
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
    }

    public function whereNotIn(
        string $column,
        array  $values,
        bool   $and = true
    ): self
    {
        if (empty($values)) {
            return $this;
        }
        $placeholders = [];
        foreach ($values as $value) {
            $placeholder = $this->bindingsManager->add($value);
            $placeholders[] = $placeholder;
        }
        $condition = new Condition(
            $column,
            'NOT IN',
            '(' . implode(', ', $placeholders) . ')',
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereNull(
        string $column,
        bool   $and = true
    ): self
    {
        $condition = new Condition($column, 'IS', 'NULL', $and ? Conjunction::AND() : Conjunction::OR());
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereNotNull(
        string $column,
        bool   $and = true
    ): self
    {
        $condition = new Condition($column, 'IS', 'NOT NULL', $and ? Conjunction::AND() : Conjunction::OR());
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereBetween(
        string                $column,
        string|int|float|bool $value1,
        string|int|float|bool $value2,
        bool                  $and = true
    ): self
    {
        $placeholder1 = $this->bindingsManager->add($value1);
        $placeholder2 = $this->bindingsManager->add($value2);
        $condition = new Condition(
            $column,
            'BETWEEN',
            "$placeholder1 AND $placeholder2",
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereNotBetween(
        string                $column,
        string|int|float|bool $value1,
        string|int|float|bool $value2,
        bool                  $and = true
    ): self
    {
        $placeholder1 = $this->bindingsManager->add($value1);
        $placeholder2 = $this->bindingsManager->add($value2);
        $condition = new Condition(
            $column,
            'NOT BETWEEN',
            "$placeholder1 AND $placeholder2",
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
        return $this;
    }
}
