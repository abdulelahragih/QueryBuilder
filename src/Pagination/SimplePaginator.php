<?php

namespace Abdulelahragih\QueryBuilder\Pagination;

use Abdulelahragih\QueryBuilder\Data\Collection;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class SimplePaginator extends AbstractPaginator implements JsonSerializable, ArrayAccess, Countable, IteratorAggregate
{

    protected bool $hasMore = true;

    public static function empty(int $limit): SimplePaginator
    {
        return new SimplePaginator(Collection::make(), 1, $limit);
    }
    public function __construct(
        array|Collection $items,
        int              $currentPage,
        int              $perPage,
    )
    {
        $this->setItems($items);
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->hasMore = $this->items->count() >= $perPage;
    }

    protected function setItems(array|Collection $items): void
    {
        $this->items = $items instanceof Collection ? $items : Collection::make($items);
    }

    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }

    public function setHasMorePages(bool $hasMore): void
    {
        $this->hasMore = $hasMore;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->items(),
            'pagination' => $this->getPaginationInfo(),
        ];
    }

    public function getPaginationInfo(): array
    {
        return [
            'perPage' => $this->perPage(),
            'currentPage' => $this->currentPage(),
            'previousPage' => $this->currentPage() > 1 ? $this->currentPage() - 1 : null,
            'nextPage' => $this->hasMorePages() ? $this->currentPage() + 1 : null,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}