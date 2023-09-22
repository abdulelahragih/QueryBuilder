<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

use Abdulelahragih\QueryBuilder\Traits\CanBuildClause;

class InsertStatement implements Statement
{
    use CanBuildClause;

    public function __construct(
        private readonly string $table,
        private readonly array  $columns,
        private readonly array  $values,
    )
    {
    }

    public function build(): string
    {
        return 'INSERT INTO ' . $this->table . ' (' . implode(', ', $this->columns) . ') VALUES ' .
            $this->buildValuesClause($this->values);
    }

    private function buildValuesClause(array $values): string
    {
        $valueClauses = [];
        if (!empty($values) && is_array(reset($values))) {
            // $values is an array of arrays (multiple rows)
            foreach ($values as $row) {
                $rowValues = implode(', ', $row);
                $valueClauses[] = '(' . $rowValues . ')';
            }
        } else {
            // $values is a single array (single row)
            $rowValues = implode(', ', $values);
            $valueClauses[] = '(' . $rowValues . ')';
        }
        return implode(', ', $valueClauses);
    }
}