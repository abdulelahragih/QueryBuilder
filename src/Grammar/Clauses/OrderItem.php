<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Types\OrderType;

class OrderItem
{
    private Expression|string $expression;
    private ?OrderType $orderType;
    private int $priority;
    private int $index;

    public function __construct(Expression|string $expression, ?OrderType $orderType = null, int $priority = 0, int $index = 0)
    {
        $this->expression = $expression;
        $this->orderType = $orderType;
        $this->priority = $priority;
        $this->index = $index;
    }

    public function getExpression(): Expression|string
    {
        return $this->expression;
    }

    public function getOrderType(): ?OrderType
    {
        return $this->orderType;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getIndex(): int
    {
        return $this->index;
    }
}
