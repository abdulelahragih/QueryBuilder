<?php

declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Dialects;

use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;

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
}
