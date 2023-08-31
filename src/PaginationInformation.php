<?php

namespace Abdulelahragih\QueryBuilder;

use JsonSerializable;

class PaginationInformation implements JsonSerializable
{
    private int $total;
    private int $start;
    private int $end;
    private int $limit;
    private int $offset;
    private int $pages;
    private int $currentPage;
    private ?int $previousPage;
    private ?int $nextPage;

    /**
     * @param int $total
     * @param int $start
     * @param int $end
     * @param int $limit
     * @param int $offset
     * @param int $pages
     * @param int $currentPage
     * @param ?int $previousPage
     * @param ?int $nextPage
     */
    public function __construct(
        int  $total,
        int  $start,
        int  $end,
        int  $limit,
        int  $offset,
        int  $pages,
        int  $currentPage,
        ?int $previousPage,
        ?int $nextPage
    ) {
        $this->total = $total;
        $this->start = $start;
        $this->end = $end;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->pages = $pages;
        $this->currentPage = $currentPage;
        $this->previousPage = $previousPage;
        $this->nextPage = $nextPage;

        if ($start > $end) {
            $this->start = $end;
        }
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getEnd(): int
    {
        return $this->end;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getPages(): int
    {
        return $this->pages;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @return ?int
     */
    public function getPreviousPage(): ?int
    {
        return $this->previousPage;
    }

    /**
     * @return ?int
     */
    public function getNextPage(): ?int
    {
        return $this->nextPage;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    public function jsonSerialize(): array
    {
        return [
            "total" => $this->total,
            "perPage" => $this->limit,
            "from" => $this->start,
            "to" => $this->end,
            "pages" => $this->pages,
            "currentPage" => $this->currentPage,
            "previousPage" => $this->currentPage > $this->pages + 1 ? $this->pages : $this->previousPage,
            "nextPage" => $this->nextPage
        ];
    }

    public static function emptyPagination(): PaginationInformation
    {
        return new PaginationInformation(0, 0, 0, 0, 0, 0, 0, null, null);
    }

    public function mergeWith(PaginationInformation $paginationInformation): self
    {
        return new self(
            $this->getTotal() + $paginationInformation->getTotal(),
            $this->getMergedLowerBound($this->getStart(), $paginationInformation->getStart()),
            $this->getMergedUpperBound($this->getEnd(), $paginationInformation->getEnd()),
            $this->getLimit() + $paginationInformation->getLimit(),
            0,
            max($this->getPages(), $paginationInformation->getPages()),
            $this->getMergedUpperBound($this->getCurrentPage(), $paginationInformation->getCurrentPage()),
            $this->getMergedLowerBound($this->getPreviousPage(), $paginationInformation->getPreviousPage()),
            $this->getMergedUpperBound($this->getNextPage(), $paginationInformation->getNextPage()),
        );
    }

    private function getMergedLowerBound(?int $value1, ?int $value2): ?int
    {
        if (isset($value1) && isset($value2)) {
            return min($value1, $value2);
        }
        return $value1 ?? $value2;
    }

    private function getMergedUpperBound(?int $value1, ?int $value2): ?int
    {
        if (isset($value1) && isset($value2)) {
            return max($value1, $value2);
        }
        return $value1 ?? $value2;
    }
}
