<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\Paginator;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PaginatorTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $p = new Paginator();
        $this->assertSame(1, $p->getPage());
        $this->assertSame(20, $p->getPerPage());
        $this->assertSame(100, $p->getMaxPerPage());
        $this->assertSame(0, $p->getOffset());
        $this->assertSame(20, $p->getLimit());
    }

    public function testGetOffsetCalculatesCorrectly(): void
    {
        $p = new Paginator(page: 3, perPage: 15);
        $this->assertSame(3, $p->getPage());
        $this->assertSame(15, $p->getPerPage());
        $this->assertSame(30, $p->getOffset()); // (3-1)*15 = 30
        $this->assertSame(15, $p->getLimit());
    }

    public function testPerPageIsClampedToMaxPerPage(): void
    {
        $p = new Paginator(page: 1, perPage: 200, maxPerPage: 100);
        $this->assertSame(100, $p->getPerPage());
    }

    public function testPageIsNeverLessThanOne(): void
    {
        $p = new Paginator(page: 0, perPage: 10);
        $this->assertSame(1, $p->getPage());
    }

    public function testPerPageIsAtLeastOne(): void
    {
        $p = new Paginator(page: 1, perPage: 0);
        $this->assertSame(1, $p->getPerPage());
    }

    public function testGetMaxPerPageReturnsConstructorValue(): void
    {
        $p = new Paginator(maxPerPage: 50);
        $this->assertSame(50, $p->getMaxPerPage());
    }
}
