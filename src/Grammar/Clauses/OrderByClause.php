<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;
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


    public function build(): string
    {
        if (empty($this->columnsAndOrderTypes)) {
            return '';
        }
        return 'ORDER BY ' . implode(
                ', ',
                array_map(
                    function ($item) {
                        return SqlUtils::quoteIdentifier($item[0]) . ' ' . $item[1]->value;
                    },
                    $this->columnsAndOrderTypes
                )
            );
    }
}