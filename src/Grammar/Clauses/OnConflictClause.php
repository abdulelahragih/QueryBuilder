<?php

declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;

class OnConflictClause implements Clause
{
    /**
     * @param array<int, Expression|string> $columns
     * @param array<string, Expression|string>|null $assignments
     */
    public function __construct(
        public readonly array $columns,
        public readonly ?array $assignments
    ) {
    }
}

