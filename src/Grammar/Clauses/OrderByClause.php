<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Types\OrderType;

class OrderByClause implements Clause
{

    private readonly array $columns;
    private readonly OrderType $orderType;

    /**
     * @param array $columns
     * @param OrderType $orderType
     */
    public function __construct(array $columns, OrderType $orderType = OrderType::Ascending)
    {
        $this->columns = $columns;
        $this->orderType = $orderType;
    }


    public function build(): string
    {
        if (empty($this->columns)) {
            return '';
        }
        return 'ORDER BY ' . implode(', ', $this->columns) . ' ' . $this->orderType->value;
    }
}