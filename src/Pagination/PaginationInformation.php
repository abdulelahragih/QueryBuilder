<?php

namespace Abdulelahragih\QueryBuilder\Pagination;

use InvalidArgumentException;
use JsonSerializable;

class PaginationInformation implements JsonSerializable
{
    private int $total;
    private int $start;
    private int $end;
    private int $perPage;
    private int $offset;
    private int $pages;
    private int $currentPage;
    private ?int $previousPage;
    private ?int $nextPage;

    public static function calculateFrom(int $total, int $perPage, int $page): PaginationInformation
    {
        if ($perPage == 0) {
            throw new InvalidArgumentException('Per page cannot be 0');
        }
        if ($page == 0) {
            throw new InvalidArgumentException('Page cannot be 0');
        }
        if ($total == 0) {
            return PaginationInformation::emptyPagination($perPage);
        }
        $pages = (int)ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $start = $offset + 1;
        $end = min(($offset + $perPage), $total);
        $previousPage = ($page > 1) ? $page - 1 : null;
        $nextPage = ($page < $pages) ? $page + 1 : null;

        return new PaginationInformation(
            $total,
            $start,
            $end,
            $perPage,
            $offset,
            $pages,
            $page,
            $previousPage,
            $nextPage
        );
    }

    /**
     * @param int $total
     * @param int $start
     * @param int $end
     * @param int $perPage
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
        int  $perPage,
        int  $offset,
        int  $pages,
        int  $currentPage,
        ?int $previousPage,
        ?int $nextPage
    )
    {
        $this->total = $total;
        $this->start = $start;
        $this->end = $end;
        $this->perPage = $perPage;
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
    public function getPerPage(): int
    {
        return $this->perPage;
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

    public static function emptyPagination(int $limit): PaginationInformation
    {
        return new PaginationInformation(0, 0, 0, $limit, 0, 1, 1, null, null);
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }

    public function toArray(): array
    {
        return [
            "total" => $this->total,
            "perPage" => $this->perPage,
            "from" => $this->start,
            "to" => $this->end,
            "pages" => $this->pages,
            "currentPage" => $this->currentPage,
            "previousPage" => $this->currentPage > $this->pages + 1 ? $this->pages : $this->previousPage,
            "nextPage" => $this->nextPage
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
