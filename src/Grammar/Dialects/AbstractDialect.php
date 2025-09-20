<?php

declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Dialects;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\Condition;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\ConditionsClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\ConditionsGroup;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\FromClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\JoinClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\LimitClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OffsetClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OnConflictClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\OrderByClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Grammar\Statements\DeleteStatement;
use Abdulelahragih\QueryBuilder\Grammar\Statements\InsertStatement;
use Abdulelahragih\QueryBuilder\Grammar\Statements\SelectStatement;
use Abdulelahragih\QueryBuilder\Grammar\Statements\UpdateStatement;
use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;
use InvalidArgumentException;

abstract class AbstractDialect implements Dialect
{
    public function compileSelect(SelectStatement $statement): string
    {
        $segments = ['SELECT'];

        if ($statement->isDistinct()) {
            $segments[] = 'DISTINCT';
        }

        $segments[] = $this->compileColumnList($statement->columns);

        if ($fromClause = $statement->getFromClause()) {
            $segments[] = $this->compileFromClause($fromClause);
        }

        foreach ($statement->getJoinClauses() ?? [] as $joinClause) {
            $segments[] = $this->compileJoinClause($joinClause);
        }

        if ($whereClause = $statement->getWhereClause()) {
            $whereSql = $this->compileWhereClause($whereClause);
            if ($whereSql !== '') {
                $segments[] = $whereSql;
            }
        }

        if ($orderByClause = $statement->getOrderByClause()) {
            $orderBySql = $this->compileOrderByClause($orderByClause);
            if ($orderBySql !== '') {
                $segments[] = $orderBySql;
            }
        }

        if ($limitClause = $statement->getLimitClause()) {
            $segments[] = $this->compileLimitClause($limitClause);
        }

        if ($offsetClause = $statement->getOffsetClause()) {
            $segments[] = $this->compileOffsetClause($offsetClause);
        }

        return $this->concatenateSegments($segments);
    }

    public function compileInsert(InsertStatement $statement): string
    {
        $columns = $this->compileInsertColumns($statement->getColumns());
        $values = $this->compileInsertValues($statement->getValues());

        $segments = [
            'INSERT INTO',
            $this->quoteIdentifier($statement->getTable()),
            $columns,
            'VALUES',
            $values,
        ];

        $upsertClause = $this->compileUpsertClause($statement->getUpdateOnDuplicateKey());
        if ($upsertClause !== '') {
            $segments[] = $upsertClause;
        }

        if ($onConflictClause = $statement->getOnConflictClause()) {
            $conflictSql = $this->compileOnConflict($onConflictClause);
            if ($conflictSql !== '') {
                $segments[] = $conflictSql;
            }
        }

        return $this->concatenateSegments($segments);
    }

    public function compileUpdate(UpdateStatement $statement): string
    {
        $statement->ensureSafe();

        $assignments = $this->compileAssignments($statement->getColumnsToValues() ?? []);
        if ($assignments === '') {
            throw new InvalidArgumentException('Update statement must specify at least one column to update.');
        }

        $segments = [
            'UPDATE',
            $this->quoteIdentifier($statement->getTable()),
            'SET ' . $assignments,
        ];

        foreach ($statement->getJoinClauses() ?? [] as $joinClause) {
            $segments[] = $this->compileJoinClause($joinClause);
        }

        if ($whereClause = $statement->getWhereClause()) {
            $whereSql = $this->compileWhereClause($whereClause);
            if ($whereSql !== '') {
                $segments[] = $whereSql;
            }
        }

        return $this->concatenateSegments($segments);
    }

    public function compileDelete(DeleteStatement $statement): string
    {
        $statement->ensureSafe();

        $segments = [
            'DELETE FROM',
            $this->quoteIdentifier($statement->getTable()),
        ];

        foreach ($statement->getJoinClauses() ?? [] as $joinClause) {
            $segments[] = $this->compileJoinClause($joinClause);
        }

        if ($whereClause = $statement->getWhereClause()) {
            $whereSql = $this->compileWhereClause($whereClause);
            if ($whereSql !== '') {
                $segments[] = $whereSql;
            }
        }

        return $this->concatenateSegments($segments);
    }

    public function compileJoinClause(JoinClause $joinClause): string
    {
        $conditionsSql = $this->compileConditionsClause($joinClause->conditionClauses);
        return trim($joinClause->joinType->value . ' JOIN ' . $this->quoteIdentifier($joinClause->table) . ' ON ' . $conditionsSql);
    }

    public function compileWhereClause(WhereClause $whereClause): string
    {
        $conditions = $whereClause->conditionClauses->getConditions();
        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . $this->compileConditions($conditions);
    }

    public function quoteIdentifier(Expression|string $identifier): string
    {
        if ($identifier instanceof Expression) {
            return $identifier->getValue();
        }

        return SqlUtils::quoteIdentifier($identifier, $this->identifierQuoteCharacter());
    }

    protected function compileFromClause(FromClause $fromClause): string
    {
        return 'FROM ' . $this->quoteIdentifier($fromClause->table);
    }

    protected function compileOrderByClause(OrderByClause $orderByClause): string
    {
        $columns = $orderByClause->getColumns();
        if (empty($columns)) {
            return '';
        }

        $parts = array_map(function (array $item): string {
            return $this->quoteIdentifier($item[0]) . ' ' . $item[1]->value;
        }, $columns);

        return 'ORDER BY ' . implode(', ', $parts);
    }

    protected function compileLimitClause(LimitClause $limitClause): string
    {
        return 'LIMIT ' . $limitClause->getLimit();
    }

    protected function compileOffsetClause(OffsetClause $offsetClause): string
    {
        return 'OFFSET ' . $offsetClause->getOffset();
    }

    protected function compileColumnList(?array $columns): string
    {
        if (empty($columns)) {
            return '*';
        }

        return SqlUtils::joinTo($columns, ', ', function ($column) {
            return $this->quoteIdentifier($column);
        });
    }

    protected function compileInsertColumns(array $columns): string
    {
        return '(' . SqlUtils::joinTo($columns, ', ', function ($column) {
            return $this->quoteIdentifier($column);
        }) . ')';
    }

    protected function compileInsertValues(array $values): string
    {
        if (!empty($values) && is_array(reset($values))) {
            $rows = array_map(function (array $row): string {
                return '(' . implode(', ', $row) . ')';
            }, $values);

            return implode(', ', $rows);
        }

        return '(' . implode(', ', $values) . ')';
    }

    protected function compileAssignments(array $columnsToValues): string
    {
        $assignments = [];
        foreach ($columnsToValues as $column => $value) {
            $assignments[] = $this->quoteIdentifier((string)$column) . ' = ' . $value;
        }

        return implode(', ', $assignments);
    }

    protected function compileConditionsClause(ConditionsClause $conditionsClause): string
    {
        return $this->compileConditions($conditionsClause->getConditions());
    }

    /**
     * @param array<int, Condition|ConditionsGroup> $conditions
     */
    protected function compileConditions(array $conditions): string
    {
        if (empty($conditions)) {
            return '';
        }

        $parts = [];
        foreach ($conditions as $index => $condition) {
            $parts[] = $this->compileConditionSegment($condition, $index === 0);
        }

        return implode(' ', array_filter($parts, static fn($part) => $part !== ''));
    }

    protected function compileConditionSegment(Condition|ConditionsGroup $condition, bool $isFirst): string
    {
        if ($condition instanceof ConditionsGroup) {
            return $this->compileConditionsGroup($condition, $isFirst);
        }

        $prefix = $isFirst ? '' : $condition->conjunction->conjunction . ' ';
        return $prefix . $this->compileCondition($condition);
    }

    protected function compileConditionsGroup(ConditionsGroup $group, bool $isFirst): string
    {
        $inner = $this->compileConditions($group->conditions);
        $prefix = $isFirst ? '' : $group->conjunction->conjunction . ' ';

        return $prefix . '(' . $inner . ')';
    }

    protected function compileCondition(Condition $condition): string
    {
        return $this->quoteIdentifier($condition->left) . ' ' . $condition->operator . ' ' . $this->quoteIdentifier($condition->right);
    }

    protected function compileUpsertClause(?array $updateOnDuplicateKey): string
    {
        if (empty($updateOnDuplicateKey)) {
            return '';
        }

        return $this->formatUpsertClause($updateOnDuplicateKey);
    }

    protected function compileOnConflict(OnConflictClause $onConflictClause): string
    {
        return '';
    }

    abstract protected function formatUpsertClause(array $updateOnDuplicateKey): string;

    protected function concatenateSegments(array $segments): string
    {
        $filtered = array_values(array_filter($segments, static fn($segment) => $segment !== null && $segment !== ''));
        return implode(' ', $filtered);
    }

    abstract protected function identifierQuoteCharacter(): string;
}
