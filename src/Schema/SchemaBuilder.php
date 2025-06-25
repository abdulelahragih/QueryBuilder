<?php
namespace Abdulelahragih\QueryBuilder\Schema;

use PDO;

class SchemaBuilder
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $table, array $columns): bool
    {
        $definitions = implode(', ', $columns);
        return $this->pdo->exec("CREATE TABLE $table ($definitions)") !== false;
    }

    public function drop(string $table): bool
    {
        return $this->pdo->exec("DROP TABLE IF EXISTS $table") !== false;
    }
}
