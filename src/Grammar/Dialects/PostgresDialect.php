<?php

declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Dialects;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\OnConflictClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;

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
}
