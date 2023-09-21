<?php

namespace Abdulelahragih\QueryBuilder\Tests\Pagination;

use Abdulelahragih\QueryBuilder\Pagination\PaginationInformation;
use PHPUnit\Framework\TestCase;

class PaginationInformationTest extends TestCase
{

    public function testCalculateFrom(): void {
        $paginationInfo = PaginationInformation::calculateFrom(92, 10, 1);
        $this->assertEquals(92, $paginationInfo->getTotal());
        $this->assertEquals(1, $paginationInfo->getStart());
        $this->assertEquals(10, $paginationInfo->getEnd());
        $this->assertEquals(10, $paginationInfo->getPerPage());
        $this->assertEquals(0, $paginationInfo->getOffset());
        $this->assertEquals(10, $paginationInfo->getPages());
        $this->assertEquals(1, $paginationInfo->getCurrentPage());
        $this->assertEquals(null, $paginationInfo->getPreviousPage());
        $this->assertEquals(2, $paginationInfo->getNextPage());
    }
}
