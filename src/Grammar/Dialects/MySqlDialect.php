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
