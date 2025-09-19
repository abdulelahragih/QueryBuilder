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

interface Dialect
{
    public function compileSelect(SelectStatement $statement): string;

    public function compileInsert(InsertStatement $statement): string;

    public function compileUpdate(UpdateStatement $statement): string;

    public function compileDelete(DeleteStatement $statement): string;

    public function quoteIdentifier(Expression|string $identifier): string;

    public function compileWhereClause(WhereClause $whereClause): string;

    public function compileJoinClause(JoinClause $joinClause): string;
}
