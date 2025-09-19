<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

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

    public function getLimit(): int
    {
        return $this->limit;
    }
}
