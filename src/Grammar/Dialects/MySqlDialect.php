<?php

declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Dialects;

use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;
use Abdulelahragih\QueryBuilder\Helpers\BindingsManager;

class MySqlDialect extends AbstractDialect
{
    protected function formatUpsertClause(array $updateOnDuplicateKey): string
    {
        $index = 0;
        $assignments = SqlUtils::joinToAssociative($updateOnDuplicateKey, ', ', function ($column, $value) use (&$index) {
            if (is_int($column) && $column === $index) {
                $index++;
                $columnName = $this->quoteIdentifier($value);
                return $columnName . ' = VALUES(' . $columnName . ')';
            }

            // if the value is a scalar (not a placeholder), generate VALUES() syntax
            if (!is_string($value) || (!str_starts_with($value, ':') && !str_starts_with($value, 'VALUES('))) {
                $columnName = $this->quoteIdentifier($column);
                return $columnName . ' = VALUES(' . $columnName . ')';
            }

            // Check if value starts with VALUES( - if so, use as is
            if (is_string($value) && str_starts_with($value, 'VALUES(')) {
                return $this->quoteIdentifier($column) . ' = ' . $value;
            }

            return $this->quoteIdentifier($column) . ' = ' . $value;
        });

        if ($assignments === '') {
            return '';
        }

        return 'ON DUPLICATE KEY UPDATE ' . $assignments;
    }

    protected function identifierQuoteCharacter(): string
    {
        return '`';
    }

    public function buildUpsertAssignments(array $columnsToValues, array $uniqueBy, ?array $updateOnDuplicate, BindingsManager $bindingsManager): array
    {
        // If updateOnDuplicate is null, update all non-unique columns using VALUES() semantics
        if ($updateOnDuplicate === null) {
            $firstRow = is_array(reset($columnsToValues)) ? $columnsToValues[0] : $columnsToValues;
            $assignments = [];
            foreach ($firstRow as $column => $value) {
                if (!in_array($column, $uniqueBy, true)) {
                    $assignments[$column] = $value; // MySQL formatter converts scalars to VALUES(col)
                }
            }
            return ['updateOnDuplicateKey' => $assignments, 'onConflictClause' => null];
        }

        // Provided explicit assignments; use given mapping, converting scalars to placeholders
        $processed = [];
        foreach ($updateOnDuplicate as $column => $value) {
            if (is_int($column)) {
                // infer from column name using VALUES(column)
                $processed[$value] = $value;
            } else {
                $processed[$column] = is_string($value) && str_starts_with($value, 'VALUES(')
                    ? $value
                    : $bindingsManager->add($value);
            }
        }
        return ['updateOnDuplicateKey' => $processed, 'onConflictClause' => null];
    }
}
