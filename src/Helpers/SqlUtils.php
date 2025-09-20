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
     * @param Expression|string $identifier The SQL identifier to sanitize such as table or column name.
     * @param string $quoteCharacter The character used for quoting (default is backtick `).
     * @return string The sanitized identifier.
     */
    public static function quoteIdentifier(Expression|string $identifier, string $quoteCharacter = '`'): string
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
            return SqlUtils::quoteIdentifier($identifier, $quoteCharacter) . ' AS ' . SqlUtils::quoteIdentifier($alias, $quoteCharacter);
        } elseif (SqlUtils::isAliasedWithSpace($identifier, $matches)) {
            // Split the identifier and alias by the last space
            $quotedIdentifier = self::quoteIdentifier($matches[1], $quoteCharacter);
            $quotedAlias = self::quoteIdentifier($matches[2], $quoteCharacter);

            // Don't add extra space if original identifier part ends with spaces
            if (str_ends_with($matches[1], ' ')) {
                return $quotedIdentifier . $quotedAlias;
            } else {
                return $quotedIdentifier . ' ' . $quotedAlias;
            }
        }

        // Handle identifiers that are already quoted - escape existing quotes
        if (str_starts_with($identifier, $quoteCharacter) && str_ends_with($identifier, $quoteCharacter)) {
            // Escape internal quotes and re-quote (don't remove existing quotes first)
            $escaped = str_replace($quoteCharacter, $quoteCharacter . $quoteCharacter, $identifier);
            return $escaped;
        }

        // Handle identifiers that contain quotes but aren't fully wrapped
        if (str_contains($identifier, $quoteCharacter)) {
            // Escape existing quotes and wrap in new quotes
            $escaped = str_replace($quoteCharacter, $quoteCharacter . $quoteCharacter, $identifier);
            return $quoteCharacter . $escaped . $quoteCharacter;
        }

        // Split identifiers by dots (e.g., user.id -> ['user', 'id'])
        $parts = explode('.', $identifier);

        // Quote each part and join them with `.`
        $quotedParts = array_map(function ($part) use ($quoteCharacter) {
            if ($part === '*') { // That is in case of SELECT tableA.*, tableB.id...
                return $part;
            }
            return $quoteCharacter . $part . $quoteCharacter;
        }, $parts);

        return implode('.', $quotedParts);
    }

    private static function isAliasedWithAs(string $identifier): bool
    {
        return str_contains(strtolower($identifier), ' as ');
    }

    private static function isAliasedWithSpace(string $identifier, &$matches = null): bool
    {
        // Find the split point manually by looking for the last word/quoted string
        $len = strlen($identifier);
        $i = $len - 1;

        // Skip trailing spaces
        while ($i >= 0 && $identifier[$i] === ' ') {
            $i--;
        }

        if ($i < 0) {
            return false; // Only spaces
        }

        // Find the start of the last token (quoted string or unquoted word)
        if ($identifier[$i] === '"' || $identifier[$i] === '`') {
            // Quoted string - find the opening quote
            $quoteChar = $identifier[$i];
            $i--;
            while ($i >= 0 && $identifier[$i] !== $quoteChar) {
                $i--;
            }
            if ($i < 0) {
                return false; // Unmatched quote
            }
        } else {
            // Unquoted word - find the start
            while ($i >= 0 && $identifier[$i] !== ' ') {
                $i--;
            }
        }

        $tokenStart = $i + 1;

        // Check if there's a space before the token
        if ($tokenStart === 0) {
            return false; // No space before token
        }

        // Find the end of the identifier (start of spaces before token)
        while ($i >= 0 && $identifier[$i] === ' ') {
            $i--;
        }

        if ($i < 0) {
            return false; // Only spaces before token
        }

        // Make sure we have actual content before the spaces (not just a single quote)
        $identifierPart = substr($identifier, 0, $tokenStart - 1);
        if (trim($identifierPart) === '' || strlen(trim($identifierPart)) <= 1) {
            return false; // No meaningful identifier part
        }

        // Extract parts
        $aliasPart = substr($identifier, $tokenStart);

        $matches = [
            $identifier,     // Full match
            $identifierPart, // Identifier part (with trailing spaces)
            $aliasPart       // Alias part
        ];

        return true;
    }
}
