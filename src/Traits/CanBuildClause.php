<?php

namespace Abdulelahragih\QueryBuilder\Traits;

use Abdulelahragih\QueryBuilder\Grammar\Clauses\Clause;

trait CanBuildClause
{
    private function buildOrEmpty(null|Clause|array $clause): string
    {
        if (is_null($clause)) {
            return '';
        }

        if (is_array($clause)) {
            if (empty($clause)) {
                return '';
            }

            $builtClause = '';
            foreach ($clause as $item) {
                $builtClause .= $item->build() . "\n";
            }
            return ' ' . trim($builtClause);
        }
        $builtClause = $clause->build();
        return empty($builtClause) ? '' : ' ' . $builtClause;
    }
}