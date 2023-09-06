<?php

namespace Abdulelahragih\QueryBuilder\Pagination;

use Abdulelahragih\QueryBuilder\Data\Collection;

class PaginatedResult
{

    public function __construct(private readonly Collection $data, private readonly PaginationInformation $paginationInfo)
    {
    }

    public function getData(): Collection
    {
        return $this->data;
    }

    public function getPaginationInfo(): PaginationInformation
    {
        return $this->paginationInfo;
    }

    public static function empty(): PaginatedResult
    {
        return new PaginatedResult(Collection::make(), PaginationInformation::emptyPagination());
    }

}
