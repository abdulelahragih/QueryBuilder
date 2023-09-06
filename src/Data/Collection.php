<?php

namespace Abdulelahragih\QueryBuilder\Data;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use RuntimeException;
use Traversable;

class Collection implements ArrayAccess, Countable, JsonSerializable, IteratorAggregate
{
    protected array $items = [];

    /**
     * Create a new collection instance.
     * @param array $items
     * @return self
     */
    public static function make(array $items = []): self
    {
        return new Collection($items);
    }

    /**
     * Create a new collection filled with numbers from start inclusive to end Inclusive.
     * @param int $start The start number of the sequence (Inclusive).
     * @param int $end The end number of the sequence (Inclusive).
     * @param int $step The increment between numbers. Defaults to 1.
     * @return self
     */
    public static function range(int $start, int $end, int $step = 1): self
    {
        if ($step === 0) {
            throw new RuntimeException('Step must not be zero');
        }
        if ($start > $end) {
            throw new RuntimeException('Start must be less than end');
        }
        return new Collection(range($start, $end, $step));
    }

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Return the first element in the collection.
     * @return mixed
     */
    public function first(): mixed
    {
        $firstItem = reset($this->items);
        if ($firstItem === false) {
            return null;
        }
        return $firstItem;
    }

    /**
     * Return the last element in the collection.
     * @return mixed
     */
    public function last(): mixed
    {
        $lastItem = end($this->items);
        if ($lastItem === false) {
            return null;
        }
        return $lastItem;
    }

    /**
     * Get an array of values from a specific key across all items in the collection.
     * @param string|int $key
     * @return array
     */
    public function pluck(string|int $key): array
    {
        $items = array_column($this->items, $key);
        $this->items = $items;
        return $items;
    }

    /**
     * Return a new collection with the items plucked from the given key.
     * @param string|int $key
     * @return self The new collection.
     */
    public function plucked(string|int $key): self
    {
        $items = array_column($this->items, $key);
        return new Collection($items);
    }

    /**
     * Applies the given callback to the collection items.
     * @param callable $callback
     * @return void
     */
    public function map(callable $callback): void
    {
        $this->items = array_map($callback, $this->items);
    }

    /**
     * Iterates over each value in the array passing them to the callback function.
     * If the callback function returns true, the current value from array is returned into the result array.
     * @param callable $predicate
     * @return void
     */
    public function filter(callable $predicate): void
    {
        $this->items = array_filter($this->items, $predicate);
    }

    /**
     *
     * @param callable $callback
     * @param mixed|null $initial
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Sort the collection items based on the given callback.
     * @param callable $callback
     * @return void
     */
    public function sort(callable $callback): void
    {
        $items = $this->items;
        usort($items, $callback);
        $this->items = $items;
    }

    /**
     * Return a new collection with the items sorted based on the given callback.
     * @param callable $callback
     * @return self The new collection.
     */
    public function sorted(callable $callback): self
    {
        $items = $this->items;
        usort($items, $callback);
        return new Collection($items);
    }

    /**
     * Reverse the collection items.
     * @return void
     */
    public function reverse(): void
    {
        $items = $this->items;
        $items = array_reverse($items);
        $this->items = $items;
    }

    /**
     * Return a new collection with the items reversed.
     * @return self The new collection.
     */
    public function reversed(): self
    {
        $items = $this->items;
        $items = array_reverse($items);
        return new Collection($items);
    }

    /**
     * Return a slice of the current collection as a new collection.
     * @param int $offset
     * @param int|null $length
     * @return self The new collection.
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new Collection(array_slice($this->items, $offset, $length));
    }

    /**
     * Merge the given collection with the current collection.
     * @param Collection $collection
     * @return void
     */
    public function merge(self $collection): void
    {
        $this->items = array_merge($this->items, $collection->items);
    }

    /**
     * Return a new collection with the items merged with the given collection.
     * @param Collection $collection
     * @return self The new collection.
     */
    public function merged(self $collection): self
    {
        return new Collection(array_merge($this->items, $collection->items));
    }

    public function diff(self $collection): void
    {
        $items = array_diff($this->items, $collection->items);
        if ($this->isIndexed()) {
            $items = array_values($items);
        }
        $this->items = $items;
    }

    /**
     *
     * @param Collection|array $collection
     * @return void
     */
    public function intersect(self|array $collection): void
    {
        if (is_array($collection)) {
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $this->items = array_intersect($collection, $this->items);
            return;
        }
        $this->items = array_intersect($collection->items, $this->items);
    }

    /**
     * Return a new collection with the items that are not present in the given collection.
     * @return Collection
     */
    public function unique(): self
    {
        return new Collection(array_unique($this->items));
    }

    /**
     * Return a new collection with the keys of the current collection.
     * @return self The new collection.
     */
    public function keys(): self
    {
        return new Collection(array_keys($this->items));
    }

    /**
     * Return a new indexed collection with the values of the current collection.
     * @return self The new collection.
     */
    public function values(): self
    {
        return new Collection(array_values($this->items));
    }

    /**
     * Return the collection items.
     * @param int $size
     * @return void
     */
    public function chunk(int $size): void
    {
        $chunks = array_chunk($this->items, $size);
        $this->items = $chunks;
    }

    /**
     * Return a new collection with the items chunked into the given size.
     * @param int $size
     * @return self The new collection.
     */
    public function chunked(int $size): self
    {
        $chunks = array_chunk($this->items, $size);
        return new Collection($chunks);
    }

    /**
     * Shuffle the collection items.
     * @return void
     */
    public function shuffle(): void
    {
        $items = $this->items;
        shuffle($items);
        $this->items = $items;
    }

    /**
     * Shuffle the collection items and return a new collection.
     * @return self The new collection.
     */
    public function shuffled(): self
    {
        $items = $this->items;
        shuffle($items);
        return new Collection($items);
    }

    /**
     * Check if the collection is empty.
     * @return bool True if the collection is empty, false otherwise.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if the collection is not empty.
     * @return bool True if the collection is not empty, false otherwise.
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->items);
    }

    /**
     * Check if the collection is indexed.
     * @return bool True if the collection is indexed, false otherwise.
     */
    public function isIndexed(): bool
    {
        return array_keys($this->items) === range(0, count($this->items) - 1);
    }

    /**
     * Check if the collection is associative.
     * @return bool True if the collection is associative, false otherwise.
     */
    public function isAssociative(): bool
    {
        return !$this->isIndexed();
    }

    /**
     * Check if all values in the collection pass the given predicate.
     * @param callable $predicate
     * @return bool True if all values pass the predicate, false otherwise.
     */
    public function all(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if (!$predicate($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if any value in the collection pass the given predicate.
     * @param callable $predicate
     * @return bool True if any value pass the predicate, false otherwise.
     */
    public function any(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the collection contains a value.
     * @param mixed $value
     * @return bool True if the collection contains the value, false otherwise.
     */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    /**
     * Find the first value in the collection that pass the given predicate.
     * @param callable $predicate
     * @return mixed The first value if exists or null.
     */
    public function find(callable $predicate): mixed
    {
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Find the last value in the collection that pass the given predicate.
     * @param callable $predicate
     * @return mixed The last value if exists or null.
     */
    public function findLast(callable $predicate): mixed
    {
        $items = array_reverse($this->items);
        foreach ($items as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }
        return null;
    }

    public function findIndex(callable $predicate): int
    {
        foreach ($this->items as $index => $item) {
            if ($predicate($item)) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Find the last index of a value in the collection that pass the given predicate.
     * @param callable $predicate
     * @return int The last index of the value if exists or -1.
     */
    public function findLastIndex(callable $predicate): int
    {
        $items = array_reverse($this->items);
        foreach ($items as $index => $item) {
            if ($predicate($item)) {
                return count($this->items) - $index - 1;
            }
        }
        return -1;
    }

    /**
     * Find the index of a value in the collection.
     * @param mixed $value
     * @return int The index of the value if exists or -1.
     */
    public function indexOf(mixed $value): int
    {
        $index = array_search($value, $this->items, true);
        return $index === false ? -1 : $index;
    }

    /**
     * Find the last index of a value in the collection.
     * @param mixed $value
     * @return int The last index of the value if exists or -1.
     */
    public function lastIndexOf(mixed $value): int
    {
        $index = array_search($value, array_reverse($this->items), true);
        return $index === false ? -1 : count($this->items) - $index - 1;
    }

    public function forEach(callable $callback): void
    {
        foreach ($this->items as $index => $item) {
            $callback($item, $index);
        }
    }

    /**
     * Remove all values that pass the given predicate test.
     * @param callable $predicate
     * @return array|null The removed items or null if no items removed.
     */
    public function removeAll(callable $predicate): ?array
    {
        $items = $this->items;
        $removedItems = [];
        foreach ($items as $index => $item) {
            if ($predicate($item)) {
                $removedItems[] = $item;
                unset($items[$index]);
            }
        }
        if ($this->isIndexed()) {
            $items = array_values($items);
        }
        $this->items = $items;
        return $removedItems;
    }

    /**
     * Remove a value by index in indexed array or by key in associative array.
     * @param int|string $indexOrKey The index or key of the value to remove.
     * @return mixed The old value if exists or null.
     */
    public function remove(int|string $indexOrKey): mixed
    {
        $items = $this->items;
        $oldValue = $items[$indexOrKey] ?? null;
        unset($items[$indexOrKey]);
        if ($this->isIndexed()) {
            $items = array_values($items);
        }
        $this->items = $items;
        return $oldValue;
    }

    /**
     * Pop the element off the end of array
     * @return mixed the popped element
     */
    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    /**
     * Push one or more elements onto the end of array
     * @param mixed ...$values
     * @return void
     */
    public function push(mixed ...$values): void
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }
    }

    /**
     * Shift an element off the beginning of array
     * @return int The number of elements in array after.
     */
    public function shift(): int
    {
        return array_shift($this->items);
    }

    /**
     * Prepend elements to the beginning of an array
     * @param mixed $value
     * @return int The number of elements in array.
     */
    public function unshift(mixed $value): int
    {
        $items = $this->items;
        $length = array_unshift($items, $value);
        $this->items = $items;
        return $length;
    }

    /**
     * @param int $index
     * @param mixed $value
     * @return mixed The old value if exists or null.
     */
    public function replace(int $index, mixed $value): mixed
    {
        $oldValue = $this->items[$index] ?? null;
        $this->items[$index] = $value;
        return $oldValue;
    }

    /**
     * Convert the collection to php array.
     * @return array
     */
    public function toArray(): array
    {
        return array_slice($this->items, 0);
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        $json = json_encode($this->items);
        if ($json === false) {
            throw new RuntimeException('Failed to encode JSON');
        }
        return $json;
    }

    // Other methods and interfaces (ArrayAccess, Countable, JsonSerializable) implementations...

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
        if ($this->isIndexed()) {
            $this->items = array_values($this->items);
        }
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
