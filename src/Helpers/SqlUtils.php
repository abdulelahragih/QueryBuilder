<?php

namespace Abdulelahragih\QueryBuilder\Helpers;

use Abdulelahragih\QueryBuilder\Grammar\Expression;

class SqlUtils
{
    /**
     * Joins an array of items with an optional callback to process each item.
     *
     * @param array $items The items to join.
     * @param string $separator The separator for joining.
     * @param callable|null $callback An optional callback to apply to each item.
     * @return string The joined string.
     */
    public static function joinTo(array $items, string $separator = ', ', ?callable $callback = null): string
    {
        // Apply callback to each item if provided
        $processedItems = $callback ? array_map($callback, $items) : $items;

        // Join the processed items
        return implode($separator, $processedItems);
    }

    /**
     * Escapes and quotes an identifier for SQL queries.
     *
     * Handles dotted identifiers like "user.id" and converts them into "`user`.`id`".
     *
     * @param Expression|string $identifier The SQL identifier to sanitize such as table or column name.
     * @return string The sanitized identifier.
     */
    public static function quoteIdentifier(Expression|string $identifier): string
    {
        if ($identifier instanceof Expression) {
            return $identifier->getValue();
        }

        // Split identifiers by dots (e.g., user.id -> ['user', 'id'])
        $parts = explode('.', $identifier);

        // Quote each part and join them with `.`
        $quotedParts = array_map(function ($part) {
            // Check if the part is already enclosed in backticks
            if (str_starts_with($part, '`') && str_ends_with($part, '`')) {
                return $part;
            }
            // Escape backticks and wrap with backticks
            return '`' . str_replace('`', '``', $part) . '`';
        }, $parts);

        return implode('.', $quotedParts);
    }
}
