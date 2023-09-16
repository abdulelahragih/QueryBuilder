<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

class FromClause implements Clause
{

    public function __construct(public readonly string $table)
    {
    }

    public function build(): string
    {
        return 'FROM ' . $this->table;
    }
}