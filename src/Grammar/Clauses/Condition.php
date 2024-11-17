<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\Helpers\SqlUtils;

class Condition implements Clause
{
    public readonly Expression|string $left;
    public readonly string $operator;
    public readonly Expression|string $right;

    public Conjunction $conjunction;

    /**
     * @param Expression|string $left
     * @param string $operator
     * @param Expression|string $right
     * @param Conjunction|null $conjunction
     */
    public function __construct(
        Expression|string $left,
        string            $operator,
        Expression|string $right,
        ?Conjunction      $conjunction = null,
    )
    {
        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
        $this->conjunction = $conjunction ?? Conjunction::AND();
    }

    public function build(): string
    {
        return SqlUtils::quoteIdentifier($this->left) . ' ' . $this->operator . ' ' . SqlUtils::quoteIdentifier($this->right);
    }
}
