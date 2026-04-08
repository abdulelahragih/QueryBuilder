<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Types\OrderType;

class OrderByClause implements Clause
{

    /**
     * @var OrderItem[]
     */
    private array $items;
    private int $counter;

    public function __construct()
    {
        $this->items = [];
        $this->counter = 0;
    }

    public function addColumn(Expression|string $name, OrderType $orderType): void
    {
        $this->items[] = new OrderItem($name, $orderType, 0, $this->nextIndex());
    }

    public function getColumns(): array
    {
        return $this->items;
    }

    public function addRandom(Expression $expr): void
    {
        // High priority to ensure random comes first when mixed with others
        $this->items[] = new OrderItem($expr, null, 1, $this->nextIndex());
    }

    private function nextIndex(): int
    {
        return $this->counter++;
    }
}
