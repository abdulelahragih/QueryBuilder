<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Types\OrderType;

class OrderByClause implements Clause
{

    private array $columnsToOrderType;

    public function __construct()
    {
        $this->columnsToOrderType = [];
    }

    public function addColumn(string $name, OrderType $orderType): void
    {
        $this->columnsToOrderType[$name] = $orderType;
    }


    public function build(): string
    {
        if (empty($this->columnsToOrderType)) {
            return '';
        }
        return 'ORDER BY ' . implode(', ', array_map(function (string $name, OrderType $orderType) {
                return $name . ' ' . $orderType->value;
            }, array_keys($this->columnsToOrderType), $this->columnsToOrderType));
    }
}