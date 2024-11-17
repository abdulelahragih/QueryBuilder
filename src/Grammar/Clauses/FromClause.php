<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;

class FromClause implements Clause
{

    public function __construct(public readonly Expression|string $table)
    {
    }

    public function build(): string
    {
        return 'FROM ' . SqlUtils::quoteIdentifier($this->table);
    }
}