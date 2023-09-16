<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Grammar\Statements;

use Abdulelahragih\QueryBuilder\Data\QueryBuilderException;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\Clause;
use Abdulelahragih\QueryBuilder\Grammar\Clauses\WhereClause;
use InvalidArgumentException;

class UpdateStatement implements Clause
{

    /**
     * @param string $table
     * @param array|null $columnsToValues
     * @param array|null $joinClause
     * @param WhereClause|null $whereClause
     * @param bool $forceUpdate
     */
    public function __construct(
        private readonly string       $table,
        public readonly ?array        $columnsToValues = null,
        private readonly ?array       $joinClause = null,
        private readonly ?WhereClause $whereClause = null,
        private bool                  $forceUpdate = false
    )
    {
    }

    /**
     * @param bool $forceUpdate
     */
    public function setForceUpdate(bool $forceUpdate): void
    {
        $this->forceUpdate = $forceUpdate;
    }

    /**
     * @throws QueryBuilderException
     */
    public function build(): string
    {
        if (!isset($this->whereClause) || empty($this->whereClause->conditionClauses->getConditions())) {
            if (!$this->forceUpdate) {
                throw new QueryBuilderException(
                    QueryBuilderException::DANGEROUS_QUERY,
                    'Update statement without where clause is not allowed. Use setForceUpdate(true) to force update.');
            }
        }
        return 'UPDATE ' . $this->table . ' SET ' .
            $this->buildSetClause($this->columnsToValues) .
            $this->buildOrEmpty($this->joinClause) .
            $this->buildOrEmpty($this->whereClause);
    }

    private function buildSetClause(array $columnsToValues): string
    {
        return implode(', ', array_map(function ($column, $value) {
            return $column . ' = ' . $value;
        }, array_keys($columnsToValues), $columnsToValues));
    }

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
