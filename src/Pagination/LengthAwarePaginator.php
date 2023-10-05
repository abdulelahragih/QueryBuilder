<?php

namespace Abdulelahragih\QueryBuilder\Pagination;

use Abdulelahragih\QueryBuilder\Data\Collection;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class LengthAwarePaginator extends AbstractPaginator implements JsonSerializable, ArrayAccess, Countable, IteratorAggregate
{

    protected PaginationInformation $paginationInfo;

    public static function empty(int $limit): LengthAwarePaginator
    {
        return new static(Collection::make(), 1, $limit, 0);
    }

    public function __construct(
        array|Collection $items,
        int              $currentPage,
        int              $perPage,
        int              $total,
    )
    {
        $this->setItems($items);
        if ($this->items->isEmpty()) {
            $this->paginationInfo = PaginationInformation::emptyPagination($perPage);

        } else {
            $this->paginationInfo = PaginationInformation::calculateFrom($total, $perPage, $currentPage);
        }
        $this->perPage = $this->paginationInfo->getPerPage();
        $this->currentPage = $this->paginationInfo->getCurrentPage();
    }

    protected function setItems(array|Collection $items): void
    {
        $this->items = $items instanceof Collection ? $items : Collection::make($items);
    }

    public function hasMorePages(): bool
    {
        $totalPages = $this->paginationInfo->getPages();
        $currentPage = $this->paginationInfo->getCurrentPage();
        return $currentPage < $totalPages;
    }

    public function toArray()
    {
        return [
            'data' => $this->items(),
            'pagination' => $this->getPaginationInfo(),
        ];
    }

    public function getPaginationInfo(): array
    {
        return $this->paginationInfo->toArray();
    }


    public function perPage(): int
    {
        return $this->paginationInfo->getPerPage();
    }

    public function currentPage(): int
    {
        return $this->paginationInfo->getCurrentPage();
    }


    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}