<?php

namespace Abdulelahragih\QueryBuilder\Builders;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\Condition;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\ConditionsGroup;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\Conjunction;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
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
        $condition = new Condition($column, $operator, Expression::make($placeholder), $and ? Conjunction::AND() : Conjunction::OR());

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
        mixed                                 $column,
        ?string                               $operator = null,
        Expression|string|int|float|bool|null $value = null
    ): self
    {
        $this->where($column, $operator, $value, false);
        return $this;
    }

    public function whereLike(
        Expression|string     $column,
        string|int|float|bool $value,
        bool                  $and = true
    ): self
    {
        $placeholder = $this->bindingsManager->add($value);
        $condition = new Condition($column, 'LIKE', Expression::make($placeholder), $and ? Conjunction::AND() : Conjunction::OR());
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereNotLike(
        Expression|string     $column,
        string|int|float|bool $value,
        bool                  $and = true
    ): self
    {
        $placeholder = $this->bindingsManager->add($value);
        $condition = new Condition($column, 'NOT LIKE', Expression::make($placeholder), $and ? Conjunction::AND() : Conjunction::OR());
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereIn(
        Expression|string $column,
        array             $values,
        bool              $and = true
    ): self
    {
        if (empty($values)) {
            $this->addEmptyWhereIn($and);
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
            Expression::make('(' . implode(', ', $placeholders) . ')'),
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
        return $this;
    }

    private function addEmptyWhereIn(
        bool $and = true
    ): void
    {
        $condition = new Condition(
            Expression::make(1),
            '=',
            Expression::make(0),
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
    }

    public function whereNotIn(
        Expression|string $column,
        array             $values,
        bool              $and = true
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
            Expression::make('(' . implode(', ', $placeholders) . ')'),
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereNull(
        Expression|string $column,
        bool              $and = true
    ): self
    {
        $condition = new Condition($column, 'IS', Expression::make('NULL'), $and ? Conjunction::AND() : Conjunction::OR());
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereNotNull(
        Expression|string $column,
        bool              $and = true
    ): self
    {
        $condition = new Condition($column, 'IS', Expression::make('NOT NULL'), $and ? Conjunction::AND() : Conjunction::OR());
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereBetween(
        Expression|string     $column,
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
            Expression::make("$placeholder1 AND $placeholder2"),
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function whereNotBetween(
        Expression|string     $column,
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
            Expression::make("$placeholder1 AND $placeholder2"),
            $and ? Conjunction::AND() : Conjunction::OR()
        );
        $this->whereClause->addCondition($condition);
        return $this;
    }

    public function raw(string $value): Expression
    {
        return Expression::make($value);
    }
}
