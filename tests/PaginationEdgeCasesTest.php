<?php

namespace Abdulelahragih\QueryBuilder\Tests;

use Abdulelahragih\QueryBuilder\Grammar\Dialects\MySqlDialect;
use Abdulelahragih\QueryBuilder\Grammar\Dialects\PostgresDialect;
use Abdulelahragih\QueryBuilder\QueryBuilder;
use Abdulelahragih\QueryBuilder\Tests\Traits\TestTrait;
use PHPUnit\Framework\TestCase;

class PaginationEdgeCasesTest extends TestCase
{
    use TestTrait;

    public function testPaginationWithEmptyResults()
    {
        // Test with a condition that returns no results
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->where('id', '=', 999) // Non-existent ID
            ->paginate(1, 10);

        $this->assertEquals(0, $result->total());
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(10, $result->perPage());
        $this->assertTrue($result->isEmpty());
        $this->assertFalse($result->hasMorePages());
    }

    public function testPaginationWithSinglePage()
    {
        // Test with results that fit in one page
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->paginate(1, 10);

        $this->assertEquals(3, $result->total()); // We have 3 users in test data
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(10, $result->perPage());
        $this->assertFalse($result->isEmpty());
        $this->assertFalse($result->hasMorePages());
    }

    public function testPaginationWithMultiplePages()
    {
        // Test with small per page to force multiple pages
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->paginate(1, 2); // 2 per page, should have 2 pages

        $this->assertEquals(3, $result->total());
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(2, $result->perPage());
        $this->assertFalse($result->isEmpty());
        $this->assertTrue($result->hasMorePages());
        $this->assertEquals(2, $result->totalPages());
    }

    public function testPaginationSecondPage()
    {
        // Test second page
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->paginate(2, 2); // Page 2, 2 per page

        $this->assertEquals(3, $result->total());
        $this->assertEquals(2, $result->currentPage());
        $this->assertEquals(2, $result->perPage());
        $this->assertFalse($result->isEmpty());
        $this->assertFalse($result->hasMorePages()); // Page 2 of 2, no more pages
        $this->assertEquals(2, $result->totalPages());
    }

    public function testPaginationWithComplexWhere()
    {
        // Test pagination with complex WHERE conditions
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->where('id', '>=', 1)
            ->where('id', '<=', 3)
            ->paginate(1, 2);

        $this->assertEquals(3, $result->total());
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(2, $result->perPage());
        $this->assertFalse($result->isEmpty());
    }

    public function testSimplePaginationWithEmptyResults()
    {
        // Test simple pagination with no results
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->where('id', '=', 999)
            ->simplePaginate(1, 10);

        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(10, $result->perPage());
        $this->assertTrue($result->isEmpty());
        $this->assertFalse($result->hasMorePages());
    }

    public function testSimplePaginationWithResults()
    {
        // Test simple pagination with results
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->simplePaginate(1, 2);

        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(2, $result->perPage());
        $this->assertFalse($result->isEmpty());
        $this->assertTrue($result->hasMorePages()); // Should have more since we have 3 users
    }

    public function testPaginationWithCustomPageName()
    {
        // Test pagination with custom page name
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->paginate(1, 10, 'custom_page');

        $this->assertEquals('custom_page', $result->getPageName());
    }

    public function testPaginationWithOrderBy()
    {
        // Test pagination with ORDER BY
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->orderBy('id', 'DESC')
            ->paginate(1, 2);

        $this->assertEquals(3, $result->total());
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(2, $result->perPage());
        $this->assertFalse($result->isEmpty());

        // Check that results are ordered correctly
        $items = $result->items();
        $this->assertCount(2, $items);
        $this->assertEquals(3, $items[0]['id']); // Should be ordered DESC
        $this->assertEquals(2, $items[1]['id']);
    }

    public function testPaginationWithLimit()
    {
        // Test pagination with LIMIT (should not interfere with pagination)
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->limit(5) // This should be overridden by pagination
            ->paginate(1, 2);

        $this->assertEquals(3, $result->total());
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(2, $result->perPage());
        // The limit is not overridden by pagination, so we get 3 items (all users)
        $this->assertCount(3, $result->items());
    }

    public function testPaginationWithOffset()
    {
        // Test pagination with OFFSET (should not interfere with pagination)
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->offset(1) // This should be overridden by pagination
            ->paginate(1, 2);

        $this->assertEquals(3, $result->total());
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(2, $result->perPage());
        $this->assertCount(2, $result->items());
    }

    public function testPaginationWithDistinct()
    {
        // Test pagination with DISTINCT
        $builder = new QueryBuilder($this->pdo);
        $result = $builder
            ->table('users')
            ->distinct()
            ->select('name')
            ->paginate(1, 2);

        $this->assertEquals(3, $result->total());
        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(2, $result->perPage());
        $this->assertFalse($result->isEmpty());
    }

    public function testPaginationEdgeCaseZeroPerPage()
    {
        // Test edge case with zero per page
        $this->expectException(\InvalidArgumentException::class);

        $builder = new QueryBuilder($this->pdo);
        $builder->table('users')->paginate(1, 0);
    }

    public function testPaginationEdgeCaseNegativePage()
    {
        // Test edge case with negative page - this should not throw (validation only checks for 0)
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->paginate(-1, 10);

        $this->assertEquals(-1, $result->currentPage());
        $this->assertEquals(10, $result->perPage());
    }

    public function testPaginationEdgeCaseNegativePerPage()
    {
        // Test edge case with negative per page - this should not throw (validation only checks for 0)
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->paginate(1, -5);

        $this->assertEquals(1, $result->currentPage());
        $this->assertEquals(-5, $result->perPage());
    }
}
