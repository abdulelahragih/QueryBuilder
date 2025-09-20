<?php

namespace Abdulelahragih\QueryBuilder\Tests;

use Abdulelahragih\QueryBuilder\Data\QueryBuilderException;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\AbstractDialect;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\MySqlDialect;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\PostgresDialect;
use Abdulelahragih\QueryBuilder\QueryBuilder;
use Abdulelahragih\QueryBuilder\Tests\Traits\TestTrait;
use PHPUnit\Framework\TestCase;

class ErrorHandlingTest extends TestCase
{
    use TestTrait;

    public function testInvalidJoinTypeThrows()
    {
        $this->expectException(QueryBuilderException::class);
        $this->expectExceptionMessage('Invalid join type INVALID');

        (new QueryBuilder($this->pdo))
            ->table('users')
            ->join('images', 'images.user_id', '=', 'users.id', 'INVALID');
    }

    public function testInvalidOrderTypeThrows()
    {
        $this->expectException(QueryBuilderException::class);
        $this->expectExceptionMessage('Invalid order type INVALID');

        (new QueryBuilder($this->pdo))
            ->table('users')
            ->orderBy('id', 'INVALID');
    }

    public function testUpdateWithArrayValueThrows()
    {
        $this->expectException(QueryBuilderException::class);
        $this->expectExceptionMessage('Value cannot be an array');

        (new QueryBuilder($this->pdo))
            ->table('users')
            ->where('id', '=', 1)
            ->update(['name' => ['invalid' => 'array']]);
    }

    public function testSelectWithoutTableThrows()
    {
        $this->expectException(QueryBuilderException::class);
        $this->expectExceptionMessage('You must specify a table');

        (new QueryBuilder($this->pdo))
            ->select('id', 'name')
            ->toSql();
    }

    public function testUpsertWithoutTableThrows()
    {
        $this->expectException(\TypeError::class);

        (new QueryBuilder($this->pdo))
            ->upsert(['id' => 1], ['id']);
    }

    public function testInsertWithoutTableThrows()
    {
        $this->expectException(\TypeError::class);

        (new QueryBuilder($this->pdo))
            ->insert(['id' => 1]);
    }

    public function testUpdateWithoutTableThrows()
    {
        $this->expectException(\TypeError::class);

        (new QueryBuilder($this->pdo))
            ->update(['name' => 'test']);
    }

    public function testDeleteWithoutTableThrows()
    {
        $this->expectException(\TypeError::class);

        (new QueryBuilder($this->pdo))
            ->delete();
    }

    public function testUpsertWithEmptyUniqueColumns()
    {
        $this->expectException(\PDOException::class);

        (new QueryBuilder($this->pdo))
            ->table('users')
            ->upsert(['id' => 1], []); // Empty unique columns
    }

    public function testUpsertWithEmptyData()
    {
        $this->expectException(\PDOException::class);

        (new QueryBuilder($this->pdo))
            ->table('users')
            ->upsert([], ['id']); // Empty data
    }

    public function testInsertWithEmptyData()
    {
        $this->expectException(\PDOException::class);

        (new QueryBuilder($this->pdo))
            ->table('users')
            ->insert([]); // Empty data
    }

    public function testUpdateWithEmptyData()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new QueryBuilder($this->pdo))
            ->table('users')
            ->where('id', '=', 1)
            ->update([]); // Empty data
    }

    public function testUnsupportedDialectUpsertThrows()
    {
        // Create a mock dialect that doesn't support upsert
        $mockDialect = new class extends AbstractDialect {
            public function compileSelect($statement): string
            {
                return '';
            }
            public function compileInsert($statement): string
            {
                return '';
            }
            public function compileUpdate($statement): string
            {
                return '';
            }
            public function compileDelete($statement): string
            {
                return '';
            }
            public function quoteIdentifier($identifier): string
            {
                return '';
            }
            public function compileWhereClause($whereClause): string
            {
                return '';
            }
            public function compileJoinClause($joinClause): string
            {
                return '';
            }
            protected function identifierQuoteCharacter(): string
            {
                return '';
            }
            protected function formatUpsertClause(array $updateOnDuplicateKey): string
            {
                return '';
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Upsert is not supported by this dialect.');

        (new QueryBuilder($this->pdo, $mockDialect))
            ->table('users')
            ->upsert(['id' => 1], ['id']);
    }

    public function testInvalidPaginationParameters()
    {
        $builder = new QueryBuilder($this->pdo);

        // Test negative page
        $this->expectException(\InvalidArgumentException::class);
        $builder->table('users')->paginate(-1, 10);

        // Test zero per page
        $this->expectException(\InvalidArgumentException::class);
        $builder->table('users')->paginate(1, 0);

        // Test negative per page
        $this->expectException(\InvalidArgumentException::class);
        $builder->table('users')->paginate(1, -5);
    }

    public function testInvalidLimitOffset()
    {
        // Test negative limit - should not throw, just use the value
        $query = (new QueryBuilder($this->pdo))->table('users')->limit(-1)->toSql();
        $this->assertEquals('SELECT * FROM `users` LIMIT -1;', $query);

        // Test negative offset - should not throw, just use the value
        $query = (new QueryBuilder($this->pdo))->table('users')->offset(-1)->toSql();
        $this->assertEquals('SELECT * FROM `users` OFFSET -1;', $query);
    }

    public function testInvalidWhereInWithNonArray()
    {
        $this->expectException(\TypeError::class);

        (new QueryBuilder($this->pdo))
            ->table('users')
            ->whereIn('id', 'not-an-array');
    }

    public function testInvalidWhereNotInWithNonArray()
    {
        $this->expectException(\TypeError::class);

        (new QueryBuilder($this->pdo))
            ->table('users')
            ->whereNotIn('id', 'not-an-array');
    }

    public function testInvalidBetweenValues()
    {
        // These should not throw, just use the values as strings
        $query = (new QueryBuilder($this->pdo))
            ->table('users')
            ->whereBetween('id', 'not-numeric', 'also-not-numeric')
            ->toSql();

        $this->assertEquals('SELECT * FROM `users` WHERE `id` BETWEEN :v1 AND :v2;', $query);
    }

    public function testInvalidNotBetweenValues()
    {
        // These should not throw, just use the values as strings
        $query = (new QueryBuilder($this->pdo))
            ->table('users')
            ->whereNotBetween('id', 'not-numeric', 'also-not-numeric')
            ->toSql();

        $this->assertEquals('SELECT * FROM `users` WHERE `id` NOT BETWEEN :v1 AND :v2;', $query);
    }
}
