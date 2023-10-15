<?php

namespace Abdulelahragih\QueryBuilder\Pagination;

use Abdulelahragih\QueryBuilder\Data\Collection;
use Exception;
use Traversable;

class Paginator
{

    protected Collection $items;
    protected int $perPage;
    protected int $currentPage;
    protected string $pageName = 'page';

    protected function isValidPageNumber($page): bool
    {
        return $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false;
    }

    public function items(): array
    {
        return $this->items->toArray();
    }

    public function firstItem(): ?int
    {
        return $this->items->count() > 0 ? ($this->currentPage - 1) * $this->perPage + 1 : null;
    }

    public function lastItem(): ?int
    {
        return $this->items->count() > 0 ? $this->firstItem() + $this->items->count() - 1 : null;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function setPageName(string $name): Paginator
    {
        $this->pageName = $name;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getIterator(): Traversable
    {
        return $this->items->getIterator();
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->items->isNotEmpty();
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function getCollection(): Collection
    {
        return $this->items;
    }

    public function setCollection(Collection $collection): static
    {
        $this->items = $collection;

        return $this;
    }

    public function offsetExists($key): bool
    {
        return $this->items->offsetExists($key);
    }

    public function offsetGet($key)
    {
        return $this->items->offsetGet($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->items->offsetSet($key, $value);
    }

    public function offsetUnset($key): void
    {
        $this->items->offsetUnset($key);
    }
}