<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Traits;

use Abdulelahragih\QueryBuilder\Pagination\PaginatedResult;
use Abdulelahragih\QueryBuilder\Pagination\PaginationInformation;
use InvalidArgumentException;

trait CanPaginate
{

    protected function paginatedQuery(string $query, int $limit, int $offset): string
    {
        return "$query LIMIT $limit OFFSET $offset";
    }

    protected function getPaginationInfo(int $total, int $page, int $limit): PaginationInformation
    {
        if ($page == 0) {
            throw new InvalidArgumentException('Page cannot be 0');
        }

        if ($limit == 0) {
            throw new InvalidArgumentException('Limit cannot be 0');
        }
        if ($total == 0) {
            return PaginationInformation::emptyPagination();
        }
        // Find out how many items are in the table
        // How many pages will there be
        $pages = (int)ceil($total / $limit);
        // Calculate the offset for the query
        $offset = ($page - 1) * $limit;
        // Some information to display to the user
        $start = $offset + 1;
        $end = min(($offset + $limit), $total);

        $previousPage = ($page > 1) ? $page - 1 : null;
        $nextPage = ($page < $pages) ? $page + 1 : null;
        return new PaginationInformation(
            $total,
            $start,
            $end,
            $limit,
            $offset,
            $pages,
            $page,
            $previousPage,
            $nextPage
        );
    }

    protected function toPaginatedResult($data, PaginationInformation $paginationInfo): PaginatedResult
    {
        return new PaginatedResult($data, $paginationInfo);
    }
}
