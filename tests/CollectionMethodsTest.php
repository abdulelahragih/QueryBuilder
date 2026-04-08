<?php

namespace Abdulelahragih\QueryBuilder\Tests;

use Abdulelahragih\QueryBuilder\Data\Collection;
use Abdulelahragih\QueryBuilder\QueryBuilder;
use Abdulelahragih\QueryBuilder\Tests\Traits\TestTrait;
use PHPUnit\Framework\TestCase;

class CollectionMethodsTest extends TestCase
{
    use TestTrait;

    public function testCollectionBasicMethods()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        // Test basic collection methods
        $this->assertFalse($result->isEmpty());
        $this->assertTrue($result->isNotEmpty());
        $this->assertEquals(3, $result->count());
    }

    public function testCollectionFirstMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $first = $result->first();
        $this->assertIsArray($first);
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
    }

    public function testCollectionLastMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $last = $result->last();
        $this->assertIsArray($last);
        $this->assertArrayHasKey('id', $last);
        $this->assertArrayHasKey('name', $last);
    }

    public function testCollectionPluckMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $names = $result->pluck('name');
        $this->assertIsArray($names);
        $this->assertCount(3, $names);
        $this->assertContains('Sam', $names);
        $this->assertContains('John', $names);
        $this->assertContains('Jane', $names);
    }

    public function testCollectionPluckWithNonExistentColumn()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $values = $result->pluck('non_existent_column');
        $this->assertIsArray($values);
        $this->assertCount(0, $values); // pluck modifies the collection and returns empty array for non-existent columns
    }

    public function testCollectionToArrayMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $array = $result->toArray();
        $this->assertIsArray($array);
        $this->assertCount(3, $array);
        $this->assertIsArray($array[0]);
    }

    public function testCollectionValuesMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $values = $result->values();
        $this->assertInstanceOf(Collection::class, $values);
        $this->assertEquals(3, $values->count());
        $this->assertIsArray($values->toArray()[0]);
    }

    public function testCollectionKeysMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $keys = $result->keys();
        $this->assertInstanceOf(Collection::class, $keys);
        $this->assertEquals(3, $keys->count());
        $this->assertEquals([0, 1, 2], $keys->toArray());
    }

    public function testCollectionMapMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $result->map(function ($item) {
            return $item['name'] . '_mapped';
        });
        
        $mapped = $result->toArray();
        $this->assertIsArray($mapped);
        $this->assertCount(3, $mapped);
        $this->assertContains('Sam_mapped', $mapped);
        $this->assertContains('John_mapped', $mapped);
        $this->assertContains('Jane_mapped', $mapped);
    }

    public function testCollectionFilterMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $result->filter(function ($item) {
            return $item['id'] > 1;
        });
        
        $filtered = $result->toArray();
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
    }

    public function testCollectionSortMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $result->sort(function ($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        
        $sorted = $result->toArray();
        $this->assertIsArray($sorted);
        $this->assertCount(3, $sorted);
        $this->assertEquals(1, $sorted[0]['id']);
        $this->assertEquals(2, $sorted[1]['id']);
        $this->assertEquals(3, $sorted[2]['id']);
    }

    public function testCollectionSortedMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $sorted = $result->sorted(function ($a, $b) {
            return $b['id'] <=> $a['id']; // Sort descending
        });
        
        $this->assertInstanceOf(Collection::class, $sorted);
        $this->assertEquals(3, $sorted->count());
        $sortedArray = $sorted->toArray();
        $this->assertEquals(3, $sortedArray[0]['id']);
        $this->assertEquals(2, $sortedArray[1]['id']);
        $this->assertEquals(1, $sortedArray[2]['id']);
    }

    public function testCollectionUniqueMethod()
    {
        // Test with simple values that can be compared
        $collection = Collection::make([1, 2, 2, 3, 3, 3]);
        
        $unique = $collection->unique();
        $this->assertInstanceOf(Collection::class, $unique);
        $this->assertEquals(3, $unique->count()); // Should be deduplicated to [1, 2, 3]
    }

    public function testCollectionChunkMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $result->chunk(2);
        $chunks = $result->toArray();
        $this->assertIsArray($chunks);
        $this->assertCount(2, $chunks); // 3 items chunked by 2 = 2 chunks
        $this->assertCount(2, $chunks[0]);
        $this->assertCount(1, $chunks[1]);
    }

    public function testCollectionChunkedMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $chunks = $result->chunked(2);
        $this->assertInstanceOf(Collection::class, $chunks);
        $chunksArray = $chunks->toArray();
        $this->assertCount(2, $chunksArray); // 3 items chunked by 2 = 2 chunks
        $this->assertCount(2, $chunksArray[0]);
        $this->assertCount(1, $chunksArray[1]);
    }

    public function testCollectionSliceMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $slice = $result->slice(1, 2);
        $this->assertInstanceOf(Collection::class, $slice);
        $this->assertEquals(2, $slice->count());
        $sliceArray = $slice->toArray();
        $this->assertEquals(2, $sliceArray[0]['id']);
        $this->assertEquals(3, $sliceArray[1]['id']);
    }

    public function testCollectionJsonSerializeMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $json = json_encode($result);
        $this->assertIsString($json);
        $this->assertStringContainsString('Sam', $json);
        $this->assertStringContainsString('John', $json);
        $this->assertStringContainsString('Jane', $json);
    }

    public function testCollectionToStringMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $string = (string) $result;
        $this->assertIsString($string);
        $this->assertStringContainsString('Sam', $string);
    }

    public function testCollectionContainsMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $this->assertTrue($result->contains(['id' => 1, 'name' => 'Sam']));
        $this->assertFalse($result->contains(['id' => 999, 'name' => 'NonExistent']));
    }

    public function testCollectionFindMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $found = $result->find(function ($item) {
            return $item['id'] === 2;
        });
        
        $this->assertIsArray($found);
        $this->assertEquals(2, $found['id']);
        $this->assertEquals('John', $found['name']);
    }

    public function testCollectionFindLastMethod()
    {
        $builder = new QueryBuilder($this->pdo);
        $result = $builder->table('users')->get();
        
        $found = $result->findLast(function ($item) {
            return $item['id'] >= 2;
        });
        
        $this->assertIsArray($found);
        $this->assertEquals(3, $found['id']);
        $this->assertEquals('Jane', $found['name']);
    }
}
