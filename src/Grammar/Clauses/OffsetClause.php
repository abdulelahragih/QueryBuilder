<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

class OffsetClause implements Clause
{
    private readonly int $offset;

    /**
     * @param int $offset
     */
    public function __construct(int $offset)
    {
        $this->offset = $offset;
    }


    public function build(): string
    {
        return 'OFFSET ' . $this->offset;
    }
}
