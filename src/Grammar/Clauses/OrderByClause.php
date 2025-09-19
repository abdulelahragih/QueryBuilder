<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Types\OrderType;

class OrderByClause implements Clause
{

    private array $columnsAndOrderTypes;

    public function __construct()
    {
        $this->columnsAndOrderTypes = [];
    }

    public function addColumn(Expression|string $name, OrderType $orderType): void
    {
        $this->columnsAndOrderTypes[] = [$name, $orderType];
    }

    public function getColumns(): array
    {
        return $this->columnsAndOrderTypes;
    }
}
