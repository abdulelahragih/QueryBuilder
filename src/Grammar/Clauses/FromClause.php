<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
class FromClause implements Clause
{

    public function __construct(public readonly Expression|string $table)
    {
    }
}
