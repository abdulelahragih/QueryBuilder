<?php

declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Dialects;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\JoinClause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Grammar\Statements\DeleteStatement;
use Abdulelahragih\QueryBuilder\Grammar\Statements\InsertStatement;
use Abdulelahragih\QueryBuilder\Grammar\Statements\SelectStatement;
use Abdulelahragih\QueryBuilder\Grammar\Statements\UpdateStatement;
use Abdulelahragih\QueryBuilder\Helpers\BindingsManager;

interface Dialect
{
    public function compileSelect(SelectStatement $statement): string;

    public function compileInsert(InsertStatement $statement): string;

    public function compileUpdate(UpdateStatement $statement): string;

    public function compileDelete(DeleteStatement $statement): string;

    public function quoteIdentifier(Expression|string $identifier): string;

    public function compileWhereClause(WhereClause $whereClause): string;

    public function compileJoinClause(JoinClause $joinClause): string;

    /**
     * Build dialect-specific assignments for upsert operations.
     * Should return an array with keys:
     * - 'updateOnDuplicateKey' => ?array (for MySQL's ON DUPLICATE KEY UPDATE)
     * - 'onConflictClause' => ?\Abdulelahragih\QueryBuilder\Grammar\Clauses\OnConflictClause (for Postgres)
     */
    public function buildUpsertAssignments(array $columnsToValues, array $uniqueBy, ?array $updateOnDuplicate, BindingsManager $bindingsManager): array;
}
