<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar;

class SelectClause implements Clause
{

    private bool $distinct = false;

    /**
     * @param array $columns
     */
    public function __construct(public readonly array $columns = [])
    {
    }

    public function setDistinct(bool $distinct): void
    {
        $this->distinct = $distinct;
    }


    public function build(): string
    {
        $distinct = $this->distinct ? 'DISTINCT ' : '';
        return "SELECT " . $distinct . (empty($this->columns) ? '*' : implode(', ', $this->columns));
    }
}
