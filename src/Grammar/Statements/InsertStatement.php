<?php

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\OnConflictClause;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
class InsertStatement implements Statement
{
    public function __construct(
        private readonly Expression|string $table,
        private readonly array             $columns,
        private readonly array             $values,
        private readonly ?array            $updateOnDuplicateKey = null,
        private readonly ?OnConflictClause $onConflictClause = null
    )
    {
    }

    public function getTable(): Expression|string
    {
        return $this->table;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getUpdateOnDuplicateKey(): ?array
    {
        return $this->updateOnDuplicateKey;
    }

    public function getOnConflictClause(): ?OnConflictClause
    {
        return $this->onConflictClause;
    }
}
