<?php declare(strict_types=1);

namespace App\Tests\Unit\Search\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Search\Entity\SearchResult;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SearchResultTest extends TestCase
{
    private function createResult(): SearchResult
    {
        return new SearchResult();
    }

    public function testConstructorInitializesIdAndCreatedAt(): void
    {
        $result = $this->createResult();
        $this->assertIsString($result->getId());
        $this->assertNotEmpty($result->getId());
        $this->assertInstanceOf(\DateTime::class, $result->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $result = $this->createResult();

        $result->setUserId('user-1');
        $this->assertSame('user-1', $result->getUserId());

        $result->setQuery('test query');
        $this->assertSame('test query', $result->getQuery());

        $result->setEntityType('article');
        $this->assertSame('article', $result->getEntityType());

        $result->setEntityId('entity-123');
        $this->assertSame('entity-123', $result->getEntityId());

        $result->setScore(0.95);
        $this->assertSame(0.95, $result->getScore());
    }
}
