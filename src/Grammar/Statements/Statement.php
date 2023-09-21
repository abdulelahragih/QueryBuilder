<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

interface Statement
{
    /**
     * @return string
     */
    public function build(): string;
}