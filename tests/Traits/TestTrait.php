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
        $this->seedFakeDataWithPdo($this->pdo);
    }

    public function seedFakeDataWithPdo(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (id INT PRIMARY KEY, name VARCHAR(255));');
        $pdo->exec('DELETE FROM users');
        $pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Sam');");
        $pdo->exec("INSERT INTO users (id, name) VALUES (2, 'John');");
        $pdo->exec("INSERT INTO users (id, name) VALUES (3, 'Jane');");
    }
}
