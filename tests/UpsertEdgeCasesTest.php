<?php

namespace Abdulelahragih\QueryBuilder\Tests;

use Abdulelahragih\QueryBuilder\Grammar\Dialects\MySqlDialect;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\PostgresDialect;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\QueryBuilder;
use Abdulelahragih\QueryBuilder\Tests\Traits\TestTrait;
use Error;
use Exception;
use PHPUnit\Framework\TestCase;

class UpsertEdgeCasesTest extends TestCase
{
    use TestTrait;

    public function testUpsertWithAllColumnsUnique()
    {
        // MySQL test
        $mysqlBuilder = new QueryBuilder($this->pdo, new MySqlDialect());
        $query = null;
        try {
            $mysqlBuilder
                ->table('users')
                ->upsert(
                    ['id' => 100, 'name' => 'John'],
                    ['id', 'name'], // All columns are unique
                    null,
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO `users` (`id`, `name`) VALUES (:v1, :v2);', $query);

        // PostgreSQL test
        $pgBuilder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        try {
            $pgBuilder
                ->table('users')
                ->upsert(
                    ['id' => 100, 'name' => 'John'],
                    ['id', 'name'], // All columns are unique
                    null,
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2);', $query);
    }

    public function testUpsertWithEmptyUpdateOnDuplicate()
    {
        // MySQL test
        $mysqlBuilder = new QueryBuilder($this->pdo, new MySqlDialect());
        $query = null;
        try {
            $mysqlBuilder
                ->table('users')
                ->upsert(
                    ['id' => 100, 'name' => 'John'],
                    ['id'],
                    [], // Empty update array
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO `users` (`id`, `name`) VALUES (:v1, :v2);', $query);

        // PostgreSQL test
        $pgBuilder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        try {
            $pgBuilder
                ->table('users')
                ->upsert(
                    ['id' => 100, 'name' => 'John'],
                    ['id'],
                    [], // Empty update array
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2);', $query);
    }

    public function testUpsertWithMixedDataTypes()
    {
        // MySQL test
        $mysqlBuilder = new QueryBuilder($this->pdo, new MySqlDialect());
        $query = null;
        try {
            $mysqlBuilder
                ->table('users')
                ->upsert(
                    [
                        'id' => 100,
                        'name' => 'John',
                        'age' => 25,
                        'active' => true,
                        'score' => 95.5,
                        'data' => null
                    ],
                    ['id'],
                    null,
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO `users` (`id`, `name`, `age`, `active`, `score`, `data`) VALUES (:v1, :v2, :v3, :v4, :v5, :v6) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `age` = VALUES(`age`), `active` = VALUES(`active`), `score` = VALUES(`score`), `data` = VALUES(`data`);', $query);

        // PostgreSQL test
        $pgBuilder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        try {
            $pgBuilder
                ->table('users')
                ->upsert(
                    [
                        'id' => 100,
                        'name' => 'John',
                        'age' => 25,
                        'active' => true,
                        'score' => 95.5,
                        'data' => null
                    ],
                    ['id'],
                    null,
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO "users" ("id", "name", "age", "active", "score", "data") VALUES (:v1, :v2, :v3, :v4, :v5, :v6) ON CONFLICT ("id") DO UPDATE SET "name" = EXCLUDED."name", "age" = EXCLUDED."age", "active" = EXCLUDED."active", "score" = EXCLUDED."score", "data" = EXCLUDED."data";', $query);
    }

    public function testUpsertWithExpressionInAssignments()
    {
        // MySQL test
        $mysqlBuilder = new QueryBuilder($this->pdo, new MySqlDialect());
        $query = null;
        try {
            $mysqlBuilder
                ->table('users')
                ->upsert(
                    ['id' => 100, 'name' => 'John'],
                    ['id'],
                    [
                        'name' => Expression::make('UPPER(?)'),
                        'updated_at' => Expression::make('NOW()')
                    ],
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO `users` (`id`, `name`) VALUES (:v1, :v2) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `updated_at` = VALUES(`updated_at`);', $query);

        // PostgreSQL test
        $pgBuilder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        try {
            $pgBuilder
                ->table('users')
                ->upsert(
                    ['id' => 100, 'name' => 'John'],
                    ['id'],
                    [
                        'name' => Expression::make('UPPER(?)'),
                        'updated_at' => Expression::make('NOW()')
                    ],
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2) ON CONFLICT ("id") DO UPDATE SET "name" = UPPER(?), "updated_at" = NOW();', $query);
    }

    public function testUpsertWithExplicitExcludedValues()
    {
        // PostgreSQL test for explicit EXCLUDED values
        $pgBuilder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        try {
            $pgBuilder
                ->table('users')
                ->upsert(
                    ['id' => 100, 'name' => 'John', 'age' => 25],
                    ['id'],
                    [
                        'name' => 'EXCLUDED.name',
                        'age' => 'EXCLUDED.age'
                    ],
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO "users" ("id", "name", "age") VALUES (:v1, :v2, :v3) ON CONFLICT ("id") DO UPDATE SET "name" = EXCLUDED."name", "age" = EXCLUDED."age";', $query);
    }

    public function testUpsertWithValuesFunctionInMySQL()
    {
        // MySQL test for explicit VALUES() function
        $mysqlBuilder = new QueryBuilder($this->pdo, new MySqlDialect());
        $query = null;
        try {
            $mysqlBuilder
                ->table('users')
                ->upsert(
                    ['id' => 100, 'name' => 'John', 'age' => 25],
                    ['id'],
                    [
                        'name' => 'VALUES(name)',
                        'age' => 'VALUES(age)'
                    ],
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO `users` (`id`, `name`, `age`) VALUES (:v1, :v2, :v3) ON DUPLICATE KEY UPDATE `name` = VALUES(name), `age` = VALUES(age);', $query);
    }

    public function testUpsertWithMultipleRowsAndMixedAssignments()
    {
        // MySQL test
        $mysqlBuilder = new QueryBuilder($this->pdo, new MySqlDialect());
        $query = null;
        try {
            $mysqlBuilder
                ->table('users')
                ->upsert(
                    [
                        ['id' => 100, 'name' => 'John', 'age' => 25],
                        ['id' => 101, 'name' => 'Jane', 'age' => 30]
                    ],
                    ['id'],
                    [
                        'name', // Infer from column name
                        'age' => 'VALUES(age)', // Explicit VALUES
                        'updated_at' => Expression::make('NOW()') // Expression
                    ],
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO `users` (`id`, `name`, `age`) VALUES (:v1, :v2, :v3), (:v4, :v5, :v6) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `age` = VALUES(age), `updated_at` = VALUES(`updated_at`);', $query);

        // PostgreSQL test
        $pgBuilder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        try {
            $pgBuilder
                ->table('users')
                ->upsert(
                    [
                        ['id' => 100, 'name' => 'John', 'age' => 25],
                        ['id' => 101, 'name' => 'Jane', 'age' => 30]
                    ],
                    ['id'],
                    [
                        'name', // Infer from column name
                        'age' => 'EXCLUDED.age', // Explicit EXCLUDED
                        'updated_at' => Expression::make('NOW()') // Expression
                    ],
                    $query
                );
        } catch (Exception | Error) {
        }
        $this->assertEquals('INSERT INTO "users" ("id", "name", "age") VALUES (:v1, :v2, :v3), (:v4, :v5, :v6) ON CONFLICT ("id") DO UPDATE SET "name" = EXCLUDED."name", "age" = EXCLUDED."age", "updated_at" = NOW();', $query);
    }
}
