<?php declare(strict_types=1);

namespace App\Tests\Unit\Search\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Search\Entity\SearchHistory;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SearchHistoryTest extends TestCase
{
    private function createHistory(): SearchHistory
    {
        return new SearchHistory();
    }

    public function testConstructorInitializesIdAndCreatedAt(): void
    {
        $history = $this->createHistory();
        $this->assertIsString($history->getId());
        $this->assertNotEmpty($history->getId());
        $this->assertInstanceOf(\DateTime::class, $history->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $history = $this->createHistory();

        $history->setUserId('user-1');
        $this->assertSame('user-1', $history->getUserId());

        $history->setQuery('symfony tutorial');
        $this->assertSame('symfony tutorial', $history->getQuery());

        $history->setResultCount(42);
        $this->assertSame(42, $history->getResultCount());
    }
}
