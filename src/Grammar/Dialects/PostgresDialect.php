<?php

declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Dialects;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\OnConflictClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;
use Abdulelahragih\QueryBuilder\Helpers\BindingsManager;

class PostgresDialect extends AbstractDialect
{
    protected function formatUpsertClause(array $updateOnDuplicateKey): string
    {
        return '';
    }

    protected function compileOnConflict(OnConflictClause $onConflictClause): string
    {
        $columns = SqlUtils::joinTo(
            $onConflictClause->columns,
            ', ',
            fn($column) => $this->quoteIdentifier($column)
        );

        $sql = 'ON CONFLICT (' . $columns . ')';

        if ($onConflictClause->assignments === null) {
            return $sql . ' DO NOTHING';
        }

        $assignments = SqlUtils::joinToAssociative(
            $onConflictClause->assignments,
            ', ',
            function ($column, $value) {
                [$rightPart1, $rightPart2] = match (true) {
                    $value instanceof Expression => ['', $value->getValue()],
                    is_string($value) && str_starts_with($value, 'EXCLUDED.') => [
                        'EXCLUDED.',
                        $this->quoteIdentifier(substr($value, strlen('EXCLUDED.'))),
                    ],
                    default => ['', $value instanceof Expression ? $value->getValue() : $value],

                };
                return $this->quoteIdentifier($column) . ' = ' . $rightPart1 . $rightPart2;
            }
        );

        return $sql . ' DO UPDATE SET ' . $assignments;
    }

    protected function identifierQuoteCharacter(): string
    {
        return '"';
    }

    public function buildUpsertAssignments(array $columnsToValues, array $uniqueBy, ?array $updateOnDuplicate, BindingsManager $bindingsManager): array
    {
        // Build OnConflictClause with EXCLUDED inference by default
        if ($updateOnDuplicate === null) {
            $firstRow = is_array(reset($columnsToValues)) ? $columnsToValues[0] : $columnsToValues;
            $assignments = [];
            foreach ($firstRow as $column => $value) {
                if (!in_array($column, $uniqueBy, true)) {
                    $assignments[$column] = 'EXCLUDED.' . $column;
                }
            }
            return [
                'updateOnDuplicateKey' => null,
                'onConflictClause' => empty($assignments) ? null : new OnConflictClause($uniqueBy, $assignments),
            ];
        }

        // Explicit assignments
        $processed = [];
        foreach ($updateOnDuplicate as $column => $value) {
            if (is_int($column)) {
                // infer from column name
                $processed[$value] = 'EXCLUDED.' . $value;
            } elseif ($value instanceof Expression) {
                $processed[$column] = $value;
            } elseif (is_string($value) && str_starts_with($value, 'EXCLUDED.')) {
                $processed[$column] = $value;
            } else {
                $processed[$column] = $bindingsManager->add($value);
            }
        }

        return [
            'updateOnDuplicateKey' => null,
            'onConflictClause' => empty($processed) ? null : new OnConflictClause($uniqueBy, $processed),
        ];
    }
}
