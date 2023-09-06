<?php

namespace Abdulelahragih\QueryBuilder\Tests\Traits;

use PDO;

trait TestTrait
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->seedFakeData();
    }

    private function seedFakeData() {
         // create table user
        $this->pdo->exec('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255));');
        // insert data
        $this->pdo->exec('INSERT INTO user (name) VALUES ("Sam");');
        $this->pdo->exec('INSERT INTO user (name) VALUES ("John");');
        $this->pdo->exec('INSERT INTO user (name) VALUES ("Jane");');
}
}