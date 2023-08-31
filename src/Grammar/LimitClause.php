<?php

namespace Abdulelahragih\QueryBuilder\Grammar;

class LimitClause implements Clause
{
    private readonly int $limit;

    /**
     * @param int $limit
     */
    public function __construct(int $limit)
    {
        $this->limit = $limit;
    }


    public function build(): string
    {
        return 'LIMIT ' . $this->limit;
    }
}
