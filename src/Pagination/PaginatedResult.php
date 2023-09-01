<?php

namespace Abdulelahragih\QueryBuilder\Pagination;

class PaginatedResult
{

    private array $data;
    private PaginationInformation $paginationInfo;

    public function __construct(array $data, PaginationInformation $paginationInfo)
    {
        $this->data = $data;
        $this->paginationInfo = $paginationInfo;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPaginationInfo(): PaginationInformation
    {
        return $this->paginationInfo;
    }

    public static function empty(): PaginatedResult
    {
        return new PaginatedResult([], PaginationInformation::emptyPagination());
    }

}
