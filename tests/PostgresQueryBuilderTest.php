<?php

namespace Abdulelahragih\QueryBuilder\Tests;

use Abdulelahragih\QueryBuilder\Builders\JoinClauseBuilder;
use Abdulelahragih\QueryBuilder\Builders\WhereQueryBuilder;
use Abdulelahragih\QueryBuilder\Data\QueryBuilderException;
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
    public function __construct()
    {
        parent::__construct('sqlite::memory:');
    }

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

    public function testSimpleSelect()
    {
        $query = (new QueryBuilder($this->pdo, new PostgresDialect()))
            ->table('users')
            ->select('id', 'name')
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users";', $query);
    }

    public function testEmptySelect()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder->table('users')->toSql();
        $this->assertEquals('SELECT * FROM "users";', $query);
    }

    public function testSimpleWhere()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->where('id', '=', 1)
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" WHERE "id" = :v1;', $query);
        $this->assertContains(1, $builder->getValues());
    }

    public function testMultipleWheres()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->where('id', '=', 1)
            ->where('name', '=', 'Sam')
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" WHERE "id" = :v1 AND "name" = :v2;', $query);
        $this->assertContains(1, $builder->getValues());
        $this->assertContains('Sam', $builder->getValues());
    }

    public function testOneLevelNestedWhereConditions()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->where('id', '=', 1)
            ->orWhere(function (WhereQueryBuilder $builder) {
                $builder->where('id', '=', 2);
                $builder->where('name', '=', 'Sam');
            })
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" WHERE "id" = :v1 OR ("id" = :v2 AND "name" = :v3);', $query);
        $this->assertContains(1, $builder->getValues());
        $this->assertContains(2, $builder->getValues());
        $this->assertContains('Sam', $builder->getValues());
    }

    public function testTwoLevelNestedWhereConditions()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->where('id', '=', 1)
            ->orWhere(function (WhereQueryBuilder $builder) {
                $builder->where('id', '=', 2);
                $builder->where('name', '=', 'Sam');
                $builder->where(function (WhereQueryBuilder $builder) {
                    $builder->where('id', '=', 3);
                    $builder->orWhere('name', '=', 'John');
                });
            })
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" WHERE "id" = :v1 OR ("id" = :v2 AND "name" = :v3 AND ("id" = :v4 OR "name" = :v5));', $query);
        $this->assertContains(1, $builder->getValues());
        $this->assertContains(2, $builder->getValues());
        $this->assertContains('Sam', $builder->getValues());
        $this->assertContains(3, $builder->getValues());
        $this->assertContains('John', $builder->getValues());
    }

    public function testWhereIn()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereIn('id', [1, 2, 3, 4, 5])
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "id" IN (:v1, :v2, :v3, :v4, :v5);', $query);
        $this->assertEquals([1, 2, 3, 4, 5], $builder->getValues());
    }

    public function testWhereNotIn()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNotIn('id', [1, 2, 3, 4, 5])
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "id" NOT IN (:v1, :v2, :v3, :v4, :v5);', $query);
        $this->assertEquals([1, 2, 3, 4, 5], $builder->getValues());
    }

    public function testWhereLike()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereLike('name', 'Sam')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "name" LIKE :v1;', $query);
        $this->assertEquals('Sam', $builder->getValues()[0]);
    }

    public function testWhereNotLike()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNotLike('name', 'Sam')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "name" NOT LIKE :v1;', $query);
        $this->assertEquals('Sam', $builder->getValues()[0]);
    }

    public function testWhereLikeWithWildcards()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereLike('name', '%Sam%')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "name" LIKE :v1;', $query);
        $this->assertEquals('%Sam%', $builder->getValues()[0]);
    }

    public function testWhereNull()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNull('name')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "name" IS NULL;', $query);
    }

    public function testWhereNotNull()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNotNull('name')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "name" IS NOT NULL;', $query);
    }

    public function testWhereBetween()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereBetween('id', 1, 10)
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "id" BETWEEN :v1 AND :v2;', $query);
        $this->assertEquals(1, $builder->getValues()[0]);
        $this->assertEquals(10, $builder->getValues()[1]);
    }

    public function testWhereNotBetween()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNotBetween('id', 1, 10)
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "id" NOT BETWEEN :v1 AND :v2;', $query);
        $this->assertEquals(1, $builder->getValues()[0]);
        $this->assertEquals(10, $builder->getValues()[1]);
    }

    public function testOrderByAscending()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->orderBy('id')
            ->orderBy('name')
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" ORDER BY "id" ASC, "name" ASC;', $query);
    }

    public function testOrderByDescending()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->orderByDesc('id')
            ->orderByDesc('name')
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" ORDER BY "id" DESC, "name" DESC;', $query);
    }

    public function testMixedOrder()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->orderBy('id')
            ->orderByDesc('name')
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" ORDER BY "id" ASC, "name" DESC;', $query);
    }

    public function testLimit()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->limit(10)
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" LIMIT 10;', $query);
    }

    public function testOffset()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->offset(10)
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" OFFSET 10;', $query);
    }

    public function testLimitAndOffset()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name')
            ->limit(10)
            ->offset(10)
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM "users" LIMIT 10 OFFSET 10;', $query);
    }

    public function testInnerJoin()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->join('images', 'images.user_id', '=', 'users.id')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" INNER JOIN "images" ON "images"."user_id" = "users"."id";', $query);
    }

    public function testLeftJoin()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->leftJoin('images', 'images.user_id', '=', 'users.id')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" LEFT JOIN "images" ON "images"."user_id" = "users"."id";', $query);
    }

    public function testRightJoin()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->rightJoin('images', 'images.user_id', '=', 'users.id')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" RIGHT JOIN "images" ON "images"."user_id" = "users"."id";', $query);
    }

    public function testMultipleJoins()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->join('posts', 'posts.user_id', '=', 'users.id')
            ->join('comments', 'comments.post_id', '=', 'posts.id')
            ->toSql();
        $this->assertEquals(
            'SELECT * FROM "users" INNER JOIN "posts" ON "posts"."user_id" = "users"."id" INNER JOIN "comments" ON "comments"."post_id" = "posts"."id";',
            $query
        );
    }

    public function testNestedJoinConditions()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->join('images', function (JoinClauseBuilder $builder) {
                $builder->on('images.user_id', '=', 'users.id');
                $builder->orWhere('images.user_id', '=', 1);
                $builder->where(function (JoinClauseBuilder $builder) {
                    $builder->where('images.user_id', '=', 3);
                    $builder->orWhere('images.id', '=', 1);
                });
            })
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" INNER JOIN "images" ON "images"."user_id" = "users"."id" OR ' .
            '"images"."user_id" = :v1 AND ("images"."user_id" = :v2 OR "images"."id" = :v3);', $query);
    }

    public function testFirst()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $result = $builder
            ->table('users')
            ->first('id');
        $this->assertEquals(1, $result);
    }

    public function testFirstWithSelect()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $result = $builder
            ->table('users')
            ->select('id')
            ->first('name'); // ignore first columns
        $this->assertEquals(1, $result);
    }

    public function testPluck()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $result = $builder
            ->table('users')
            ->limit(3)
            ->pluck('id');
        $this->assertEquals([1, 2, 3], $result);
    }

    public function testSingleInsert()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        $builder
            ->table('users')
            ->insert(
                [
                    'id' => 100,
                    'name' => 'John'
                ],
                $query);
        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2);', $query);
        $name = $builder->table('users')->where('id', '=', 100)->first('name');
        $this->assertEquals('John', $name);
    }

    public function testMultipleInsert()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        $builder
            ->table('users')
            ->insert(
                [
                    [
                        'id' => 100,
                        'name' => 'John'
                    ],
                    [
                        'id' => 101,
                        'name' => 'Jane'
                    ]
                ],
                $query);
        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2), (:v3, :v4);', $query);
        $name = $builder->table('users')->where('id', '=', 100)->first('name');
        $this->assertEquals('John', $name);
        $name = $builder->table('users')->where('id', '=', 101)->first('name');
        $this->assertEquals('Jane', $name);
    }

    public function testInsertOnConflictDoNothing()
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
                        'name' => 'John'
                    ],
                    $query
                );
        } catch (Exception|Error) {
        }

        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2) ON CONFLICT ("id") DO NOTHING;', $query);
    }

    public function testInsertOnConflictDoUpdateWithExpression()
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
                        'name' => 'John'
                    ],
                    $query
                );
        } catch (Exception|Error) {
        }

        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2) ON CONFLICT ("id") DO UPDATE SET "name" = EXCLUDED.name;', $query);
    }

    public function testInsertOnConflictDoUpdateInfersAssignments()
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
                        'name' => 'John'
                    ],
                    $query
                );
        } catch (Exception|Error) {
        }

        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, :v2) ON CONFLICT ("id") DO UPDATE SET "name" = :v4;', $query);
    }

    public function testUpdate()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        $builder
            ->table('users')
            ->where('id', '=', 1)
            ->update(
                [
                    'name' => 'Sarah'
                ],
                $query);
        $this->assertEquals('UPDATE "users" SET "name" = :v2 WHERE "id" = :v1;', $query);
        $name = $builder->table('users')->where('id', '=', 1)->first('name');
        $this->assertEquals('Sarah', $name);
    }

    public function testUpdateWithoutWhereThrows()
    {
        $this->expectException(QueryBuilderException::class);

        (new QueryBuilder($this->pdo, new PostgresDialect()))
            ->table('users')
            ->update(['name' => 'Test']);
    }

    public function testDelete()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        $builder
            ->table('users')
            ->where('id', '=', 1)
            ->orWhere('id', '=', 2)
            ->delete($query);
        $this->assertEquals('DELETE FROM "users" WHERE "id" = :v1 OR "id" = :v2;', $query);
        $name = $builder->table('users')->where('id', '=', 1)->first('name');
        $this->assertNull($name);
        $name = $builder->table('users')->where('id', '=', 2)->first('name');
        $this->assertNull($name);
    }

    public function testDeleteWithoutWhereThrows()
    {
        $this->expectException(QueryBuilderException::class);

        (new QueryBuilder($this->pdo, new PostgresDialect()))
            ->table('users')
            ->delete();
    }

    public function testEmptyWhereIn()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereIn('id', [])
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE 1 = 0;', $query);
    }

    public function testDistinct()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->distinct()
            ->select('id')
            ->toSql();
        $this->assertEquals('SELECT DISTINCT "id" FROM "users";', $query);
    }

    public function testRawExpressionInSelect()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->select('id', 'name', $builder->raw('COUNT(*) as count'))
            ->toSql();
        $this->assertEquals('SELECT "id", "name", COUNT(*) as count FROM "users";', $query);
    }

    public function testRawExpressionInTableName()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table($builder->raw('users'))
            ->select('id', 'name')
            ->toSql();
        $this->assertEquals('SELECT "id", "name" FROM users;', $query);
    }

    public function testRawExpressionInWhere()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->where($builder->raw('1'), '=', 1)
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE 1 = :v1;', $query);
    }

    public function testRawExpressionInWhereIn()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereIn($builder->raw('id'), [1, 2])
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE id IN (:v1, :v2);', $query);
    }

    public function testRawExpressionInWhereNotIn()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNotIn($builder->raw('id'), [1, 2])
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE id NOT IN (:v1, :v2);', $query);
    }

    public function testRawExpressionInWhereLike()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereLike($builder->raw('name'), 'Sam')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE name LIKE :v1;', $query);
    }

    public function testRawExpressionInWhereNotLike()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNotLike($builder->raw('name'), 'Sam')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE name NOT LIKE :v1;', $query);
    }

    public function testRawExpressionInWhereNull()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNull($builder->raw('name'))
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE name IS NULL;', $query);
    }

    public function testRawExpressionInWhereNotNull()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNotNull($builder->raw('name'))
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE name IS NOT NULL;', $query);
    }

    public function testRawExpressionInWhereBetween()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereBetween($builder->raw('id'), 1, 10)
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE id BETWEEN :v1 AND :v2;', $query);
    }

    public function testRawExpressionInWhereNotBetween()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->whereNotBetween($builder->raw('id'), 1, 10)
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE id NOT BETWEEN :v1 AND :v2;', $query);
    }

    public function testRawExpressionInOrderBy()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->orderBy($builder->raw('id'))
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" ORDER BY id ASC;', $query);
    }

    public function testRawExpressionInJoin()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->join($builder->raw('images'), 'images.user_id', '=', 'users.id')
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" INNER JOIN images ON "images"."user_id" = "users"."id";', $query);
    }

    public function testRawExpressionInNestedWhereConditions()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users')
            ->where('id', '=', 1)
            ->orWhere(function (WhereQueryBuilder $builder) {
                $builder->where($builder->raw('id'), '=', 2);
                $builder->where('name', '=', 'Sam');
            })
            ->toSql();
        $this->assertEquals('SELECT * FROM "users" WHERE "id" = :v1 OR (id = :v2 AND "name" = :v3);', $query);
    }

    public function testRawExpressionInInsert()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        $builder
            ->table('users')
            ->insert(
                [
                    'id' => 100,
                    'name' => $builder->raw("'John' || ' Doe'")
                ],
                $query);
        $this->assertEquals('INSERT INTO "users" ("id", "name") VALUES (:v1, \'John\' || \' Doe\');', $query);
        $name = $builder->table('users')->where('id', '=', 100)->first('name');
        $this->assertEquals('John Doe', $name);
    }

    public function testRawExpressionInUpdate()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = null;
        $builder
            ->table('users')
            ->where('id', '=', 1)
            ->update(
                [
                    'name' => $builder->raw("'Sarah' || ' Connor'")
                ],
                $query);
        $this->assertEquals('UPDATE "users" SET "name" = \'Sarah\' || \' Connor\' WHERE "id" = :v1;', $query);
        $name = $builder->table('users')->where('id', '=', 1)->first('name');
        $this->assertEquals('Sarah Connor', $name);
    }

    public function testAliasing()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users AS u')
            ->select('u.id AS user_id', 'u.name AS user_name')
            ->toSql();
        $this->assertEquals('SELECT "u"."id" AS "user_id", "u"."name" AS "user_name" FROM "users" AS "u";', $query);
    }

    public function testAliasingWithoutAs()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users u')
            ->select('u.id user_id', 'u.name user_name')
            ->toSql();
        $this->assertEquals('SELECT "u"."id" "user_id", "u"."name" "user_name" FROM "users" "u";', $query);
    }

    public function testTableAliasingWithJoins()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('users u')
            ->join('orders o', 'o.user_id', '=', 'u.id')
            ->join('orders AS o2', 'o2.user_id', '=', 'u.id')
            ->select('u.id user_id', 'u.name user_name', 'o.amount')
            ->toSql();
        $this->assertEquals(
            'SELECT "u"."id" "user_id", "u"."name" "user_name", "o"."amount" FROM "users" "u" INNER JOIN "orders" "o" ON "o"."user_id" = "u"."id" INNER JOIN "orders" AS "o2" ON "o2"."user_id" = "u"."id";',
            $query
        );
    }

    public function testAggregateFunctions()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('orders')
            ->select($builder->raw('COUNT(*) AS total_orders'))
            ->toSql();
        $this->assertEquals('SELECT COUNT(*) AS total_orders FROM "orders";', $query);
    }

    public function testSpecialCharactersInIdentifiers()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table('"users"')
            ->select('"id"', '"name"')
            ->toSql();
        $this->assertEquals('SELECT ""id"", ""name"" FROM ""users"";', $query);
    }

    public function testSpaceEscaping()
    {
        $builder = new QueryBuilder($this->pdo, new PostgresDialect());
        $query = $builder
            ->table(' users ')
            ->select(' id  " uid"', 'name ')
            ->toSql();
        $this->assertEquals('SELECT " id  "" uid""", "name " FROM " users ";', $query);
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
