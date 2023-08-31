<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar;

interface Clause
{
    /**
     * @return string
     */
    public function build(): string;
}