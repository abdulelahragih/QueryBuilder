<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;
use Abdulelahragih\QueryBuilder\Traits\CanBuildClause;

class InsertStatement implements Statement
{
    use CanBuildClause;

    public function __construct(
        private readonly Expression|string $table,
        private readonly array             $columns,
        private readonly array             $values,
        private readonly ?array            $updateOnDuplicateKey = null
    )
    {
    }

    public function build(): string
    {
        $columns = SqlUtils::joinTo($this->columns, ', ', fn($column) => SqlUtils::quoteIdentifier($column));
        return 'INSERT INTO ' . SqlUtils::quoteIdentifier($this->table) . ' (' . $columns . ') VALUES ' .
            $this->buildValuesClause($this->values) . $this->buildOnDuplicateKeyUpdateClause();
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

    private function buildOnDuplicateKeyUpdateClause(): string
    {
        if (empty($this->updateOnDuplicateKey)) {
            return '';
        }
        $updateColumns = SqlUtils::joinToAssociative($this->updateOnDuplicateKey, ', ', function ($column, $value) {
            return SqlUtils::quoteIdentifier($column) . ' = ' . $value;
        });
        return ' ON DUPLICATE KEY UPDATE ' . $updateColumns;

    }
}