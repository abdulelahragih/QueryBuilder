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
        $processedItems = $callback ? array_map($callback, $items) : $items;

        return implode($separator, $processedItems);
    }

    /**
     * Joins an associative array of items with an optional callback to process each item.
     *
     * @param array $items The items to join.
     * @param string $separator The separator for joining.
     * @param callable|null $callback An optional callback to apply to each item.
     * @return string The joined string.
     */
    public static function joinToAssociative(array $items, string $separator = ', ', ?callable $callback = null): string
    {
        $processedItems = $callback ? array_map($callback, array_keys($items), $items) : $items;

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

        $matches = null;
        if (SqlUtils::isAliasedWithAs($identifier)) {
            // Split the identifier and alias
            if (str_contains($identifier, ' AS ')) {
                [$identifier, $alias] = explode(' AS ', $identifier);
            } else {
                [$identifier, $alias] = explode(' as ', $identifier);
            }

            // Quote the identifier and alias separately
            return SqlUtils::quoteIdentifier($identifier) . ' AS ' . SqlUtils::quoteIdentifier($alias);
        } elseif (SqlUtils::isAliasedWithSpace($identifier, $matches)) {
            // Split the identifier and alias by the last space
            $quotedIdentifier = self::quoteIdentifier($matches[1]);
            $quotedAlias = self::quoteIdentifier($matches[2]);

            return $quotedIdentifier . ' ' . $quotedAlias;
        }

        // Split identifiers by dots (e.g., user.id -> ['user', 'id'])
        $parts = explode('.', $identifier);

        // Quote each part and join them with `.`
        $quotedParts = array_map(function ($part) {
            if ($part === '*') { // That is in case of SELECT tableA.*, tableB.id...
                return $part;
            }
            $part = str_replace('`', '``', $part);
            return '`' . $part . '`';
        }, $parts);

        return implode('.', $quotedParts);
    }

    private static function isAliasedWithAs(string $identifier): bool
    {
        return str_contains(strtolower($identifier), ' as ');
    }

    private static function isAliasedWithSpace(string $identifier, &$matches = null): bool
    {
        return preg_match('/^(\s*(?:[^ \t\n\r`]+|`[^`]+`))\s+((?:[^ \t\n\r`]+|`[^`]+`)\s*)$/', $identifier, $matches);
    }
}
