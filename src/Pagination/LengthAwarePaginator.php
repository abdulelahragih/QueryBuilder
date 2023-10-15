<?php

namespace Abdulelahragih\QueryBuilder\Pagination;

use Abdulelahragih\QueryBuilder\Data\Collection;
use ArrayAccess;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;

class LengthAwarePaginator extends Paginator implements JsonSerializable, ArrayAccess, Countable, IteratorAggregate
{

    private int $total;
    private int $start;
    private int $end;
    private int $offset;
    private int $pages;
    private ?int $previousPage;
    private ?int $nextPage;

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
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->total = $total;
        $this->calculatePaginationInformation();
    }

    private function calculatePaginationInformation(): void
    {
        if ($this->perPage == 0) {
            throw new InvalidArgumentException('Per page cannot be 0');
        }
        if ($this->currentPage == 0) {
            throw new InvalidArgumentException('Page cannot be 0');
        }
        if ($this->total == 0) {
            $this->pages = 0;
            $this->offset = 0;
            $this->start = 0;
            $this->end = 0;
            $this->previousPage = null;
            $this->nextPage = null;
            return;
        }
        $this->pages = (int)ceil($this->total / $this->perPage);
        $this->offset = ($this->currentPage - 1) * $this->perPage;
        $this->start = $this->offset + 1;
        $this->end = min(($this->offset + $this->perPage), $this->total);
        $this->previousPage = ($this->currentPage > 1) ? $this->currentPage - 1 : null;
        $this->nextPage = ($this->currentPage < $this->pages) ? $this->currentPage + 1 : null;
    }

    protected function setItems(array|Collection $items): void
    {
        $this->items = $items instanceof Collection ? $items : Collection::make($items);
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->pages;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function start(): int
    {
        return $this->start;
    }

    public function end(): int
    {
        return $this->end;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function totalPages(): int
    {
        return $this->pages;
    }

    public function previousPage(): ?int
    {
        return $this->previousPage;
    }

    public function nextPage(): ?int
    {
        return $this->nextPage;
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
            "total" => $this->total,
            "perPage" => $this->perPage,
            "from" => $this->start,
            "to" => $this->end,
            "pages" => $this->pages,
            "currentPage" => $this->currentPage,
            "previousPage" => $this->previousPage,
            "nextPage" => $this->nextPage
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}