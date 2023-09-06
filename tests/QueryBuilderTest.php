<?php

namespace Abdulelahragih\QueryBuilder\Tests;

use Abdulelahragih\QueryBuilder\Builders\JoinClauseBuilder;
use Abdulelahragih\QueryBuilder\Builders\WhereQueryBuilder;
use Abdulelahragih\QueryBuilder\QueryBuilder;
use Abdulelahragih\QueryBuilder\Tests\Traits\TestTrait;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    use TestTrait;

    public function testSimpleSelect()
    {
        $query = (new QueryBuilder($this->pdo))
            ->table('user')
            ->select('id', 'name')
            ->toSql();
        $this->assertEquals('SELECT id, name FROM user;', $query);
    }

    public function testSimpleWhere()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->select('id', 'name')
            ->where('id', '=', 1)
            ->toSql();
        $this->assertEquals('SELECT id, name FROM user WHERE id = :v1;', $query);
        $this->assertContains(1, $builder->getValues());
    }

    public function testMultipleWheres()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->select('id', 'name')
            ->where('id', '=', 1)
            ->where('name', '=', 'Sam')
            ->toSql();
        $this->assertEquals('SELECT id, name FROM user WHERE id = :v1 AND name = :v2;', $query);
        $this->assertContains(1, $builder->getValues());
        $this->assertContains('Sam', $builder->getValues());
    }

    public function testOneLevelNestedWhereConditions()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->select('id', 'name')
            ->where('id', '=', 1)
            ->orWhere(function (WhereQueryBuilder $builder) {
                $builder->where('id', '=', 2);
                $builder->where('name', '=', 'Sam');
            })
            ->toSql();
        $this->assertEquals('SELECT id, name FROM user WHERE id = :v1 OR (id = :v2 AND name = :v3);', $query);
        $this->assertContains(1, $builder->getValues());
        $this->assertContains(2, $builder->getValues());
        $this->assertContains('Sam', $builder->getValues());
    }

    public function testTwoLevelNestedWhereConditions()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
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
        $this->assertEquals('SELECT id, name FROM user WHERE id = :v1 OR (id = :v2 AND name = :v3 AND (id = :v4 OR name = :v5));', $query);
        $this->assertContains(1, $builder->getValues());
        $this->assertContains(2, $builder->getValues());
        $this->assertContains('Sam', $builder->getValues());
        $this->assertContains(3, $builder->getValues());
        $this->assertContains('John', $builder->getValues());
    }

    public function testWhereIn()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->whereIn('id', [1, 2, 3, 4, 5])
            ->toSql();
        $this->assertEquals('SELECT * FROM user WHERE id IN (:v1, :v2, :v3, :v4, :v5);', $query);
        $this->assertEquals([1, 2, 3, 4, 5], $builder->getValues());
    }

    public function testWhereNotIn()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->whereNotIn('id', [1, 2, 3, 4, 5])
            ->toSql();
        $this->assertEquals('SELECT * FROM user WHERE id NOT IN (:v1, :v2, :v3, :v4, :v5);', $query);
        $this->assertEquals([1, 2, 3, 4, 5], $builder->getValues());
    }

    public function testWhereLike()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->whereLike('name', 'Sam')
            ->toSql();
        $this->assertEquals('SELECT * FROM user WHERE name LIKE :v1;', $query);
        $this->assertEquals('Sam', $builder->getValues()[0]);
    }

    public function testWhereNotLike()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->whereNotLike('name', 'Sam')
            ->toSql();
        $this->assertEquals('SELECT * FROM user WHERE name NOT LIKE :v1;', $query);
        $this->assertEquals('Sam', $builder->getValues()[0]);
    }

    public function testWhereLikeWithWildcards()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->whereLike('name', '%Sam%')
            ->toSql();
        $this->assertEquals('SELECT * FROM user WHERE name LIKE :v1;', $query);
        $this->assertEquals('%Sam%', $builder->getValues()[0]);
    }

    public function testWhereNull()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->whereNull('name')
            ->toSql();
        $this->assertEquals('SELECT * FROM user WHERE name IS NULL;', $query);
    }

    public function testWhereNotNull()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->whereNotNull('name')
            ->toSql();
        $this->assertEquals('SELECT * FROM user WHERE name IS NOT NULL;', $query);
    }

    public function testWhereBetween()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->whereBetween('id', 1, 10)
            ->toSql();
        $this->assertEquals('SELECT * FROM user WHERE id BETWEEN :v1 AND :v2;', $query);
        $this->assertEquals(1, $builder->getValues()[0]);
        $this->assertEquals(10, $builder->getValues()[1]);
    }

    public function testWhereNotBetween()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->whereNotBetween('id', 1, 10)
            ->toSql();
        $this->assertEquals('SELECT * FROM user WHERE id NOT BETWEEN :v1 AND :v2;', $query);
        $this->assertEquals(1, $builder->getValues()[0]);
        $this->assertEquals(10, $builder->getValues()[1]);
    }

    public function testOrderByAscending()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->select('id', 'name')
            ->orderBy(['id', 'name'])
            ->toSql();
        $this->assertEquals('SELECT id, name FROM user ORDER BY id, name ASC;', $query);
    }

    public function testOrderByDescending()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->select('id', 'name')
            ->orderBy(['id', 'name'], 'DESC')
            ->toSql();
        $this->assertEquals('SELECT id, name FROM user ORDER BY id, name DESC;', $query);
    }

    public function testLimit()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->select('id', 'name')
            ->limit(10)
            ->toSql();
        $this->assertEquals('SELECT id, name FROM user LIMIT 10;', $query);
    }

    public function testOffset()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->select('id', 'name')
            ->offset(10)
            ->toSql();
        $this->assertEquals('SELECT id, name FROM user OFFSET 10;', $query);
    }

    public function testLimitAndOffset()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->select('id', 'name')
            ->limit(10)
            ->offset(10)
            ->toSql();
        $this->assertEquals('SELECT id, name FROM user LIMIT 10 OFFSET 10;', $query);
    }

    public function testInnerJoin()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->join('images', 'images.user_id', '=', 'user.id')
            ->toSql();
        $this->assertEquals('SELECT * FROM user INNER JOIN images ON images.user_id = user.id;', $query);
    }

    public function testLeftJoin()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->leftJoin('images', 'images.user_id', '=', 'user.id')
            ->toSql();
        $this->assertEquals('SELECT * FROM user LEFT JOIN images ON images.user_id = user.id;', $query);
    }

    public function testRightJoin()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->rightJoin('images', 'images.user_id', '=', 'user.id')
            ->toSql();
        $this->assertEquals('SELECT * FROM user RIGHT JOIN images ON images.user_id = user.id;', $query);
    }

    public function testNestedJoinConditions()
    {
        $builder = new QueryBuilder($this->pdo);
        $query = $builder
            ->table('user')
            ->join('images', function (JoinClauseBuilder $builder) {
                $builder->on('images.user_id', '=', 'user.id');
                $builder->orWhere('images.user_id', '=', 1);
                $builder->where(function (JoinClauseBuilder $builder) {
                    $builder->where('images.user_id', '=', 3);
                    $builder->orWhere('images.id', '=', 1);
                });
            })
            ->toSql();
        $this->assertEquals('SELECT * FROM user INNER JOIN images ON images.user_id = user.id OR ' .
            'images.user_id = :v1 AND (images.user_id = :v2 OR images.id = :v3);', $query);
    }

    public function testFirst() {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('user')
            ->first('id');
        $this->assertEquals(1, $result);
    }

    public function testPluck() {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('user')
            ->limit(3)
            ->pluck('id');
        $this->assertEquals([1, 2, 3], $result);
    }
}
