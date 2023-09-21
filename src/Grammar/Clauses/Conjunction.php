<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Clauses;

use InvalidArgumentException;

class Conjunction implements Clause
{
    /**
     * @param string $conjunction
     */
    public function __construct(public readonly string $conjunction)
    {
        if (!in_array(strtolower($conjunction), ['and', 'or'], true)) {
            throw new InvalidArgumentException('Invalid conjunction ' . $conjunction);
        }
    }

    public static function AND(): Conjunction
    {
        return new Conjunction('AND');
    }

    public static function OR(): Conjunction
    {
        return new Conjunction('OR');
    }

    function build(): string
    {
        return $this->conjunction;
    }
}
