<?php

namespace Abdulelahragih\QueryBuilder\Tests\Traits;

use Exception;
use PDO;

trait TestTrait
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->seedFakeData();
    }

    private function seedFakeData()
    {
        // create table user
        $this->pdo->exec('
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY,
            name VARCHAR(255)
                                         );
');
        // delete all data
        $this->pdo->exec('DELETE FROM users');
        // insert data
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Sam');");
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (2, 'John');");
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (3, 'Jane');");
    }
}
