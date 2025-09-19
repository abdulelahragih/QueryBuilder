<?php

namespace Abdulelahragih\QueryBuilder\Tests;

use Abdulelahragih\QueryBuilder\DB;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\PostgresDialect;
use Abdulelahragih\QueryBuilder\Grammar\Expression;
use Abdulelahragih\QueryBuilder\QueryBuilder;
use Abdulelahragih\QueryBuilder\Tests\Traits\TestTrait;
use Error;
use Exception;
use PDO;
use PHPUnit\Framework\TestCase;

class PostgresStubPDO extends PDO
{
    #[\ReturnTypeWillChange]
    public function getAttribute($attribute)
    {
        if ($attribute === PDO::ATTR_DRIVER_NAME) {
            return 'pgsql';
        }

        return null;
    }
}

class PostgresQueryBuilderTest extends TestCase
{
    use TestTrait;

    public function testSimpleSelectPostgres()
    {
        $query = (new QueryBuilder($this->pdo, new PostgresDialect()))
            ->table('users')
            ->select('id', 'name')
            ->toSql();

        $this->assertEquals('SELECT "id", "name" FROM "users";', $query);
    }

    public function testPostgresInsertOnConflictDoNothing()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;

        try {
            $builder
                ->table('users')
                ->onConflictDoNothing('id')
                ->insert(
                    [
                        'id' => 100,
                        'name' => 'John',
                    ],
                    $query
                );
        } catch (Exception|Error) {
        }

        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2) ON CONFLICT ("id") DO NOTHING;', $query);
    }

    public function testPostgresUpsertOnConflictDoUpdate()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;

        try {
            $builder
                ->table('users')
                ->onConflictDoUpdate('id', ['name' => Expression::make('EXCLUDED.name')])
                ->insert(
                    [
                        'id' => 100,
                        'name' => 'John',
                    ],
                    $query
                );
        } catch (Exception|Error) {
        }

        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2) ON CONFLICT ("id") DO UPDATE SET "name" = EXCLUDED.name;', $query);
    }

    public function testPostgresOnConflictDoUpdateInferredAssignments()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;

        try {
            $builder
                ->table('users')
                ->onConflictDoUpdate('id')
                ->insert(
                    [
                        'id' => 100,
                        'name' => 'John',
                    ],
                    $query
                );
        } catch (Exception|Error) {
        }

        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2) ON CONFLICT ("id") DO UPDATE SET "name" = :v4;', $query);
    }

    public function testDialectAutoDetectionForPostgres()
    {
        $builder = new QueryBuilder(new PostgresStubPDO());
        $query = $builder
            ->table('users')
            ->select('id')
            ->toSql();

        $this->assertEquals('SELECT "id" FROM "users";', $query);
    }

    public function testDbDialectAutoDetection()
    {
        $db = new DB(new PostgresStubPDO());
        $query = $db
            ->table('users')
            ->select('id')
            ->toSql();

        $this->assertEquals('SELECT "id" FROM "users";', $query);
    }
}
