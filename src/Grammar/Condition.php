<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar;

class Condition implements Clause
{
    public readonly string $left;
    public readonly string $operator;
    public readonly string $right;

    public Conjunction $conjunction;

    /**
     * @param string $left
     * @param string $operator
     * @param string $right
     * @param Conjunction|null $conjunction
     */
    public function __construct(
        string       $left,
        string       $operator,
        string       $right,
        ?Conjunction $conjunction = null,
    ) {
        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
        $this->conjunction = $conjunction ?? Conjunction::AND();
    }

    public function build(): string
    {
        return $this->left . ' ' . $this->operator . ' ' . $this->right;
    }
}
