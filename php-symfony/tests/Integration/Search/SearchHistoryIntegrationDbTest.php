<?php

declare(strict_types=1);

namespace App\Tests\Integration\Search;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Search\Entity\SearchHistory;

#[AllowMockObjectsWithoutExpectations]
class SearchHistoryIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrieveSearchHistory(): void
    {
        $history = new SearchHistory();
        $history->setUserId('user-123');
        $history->setQuery('symfony integration testing');
        $history->setResultCount(42);

        $em = self::getEntityManager();
        $em->persist($history);
        $em->flush();

        $this->assertNotNull($history->getId());

        $found = $em->getRepository(SearchHistory::class)->find($history->getId());
        $this->assertNotNull($found);
        $this->assertSame('user-123', $found->getUserId());
        $this->assertSame('symfony integration testing', $found->getQuery());
        $this->assertSame(42, $found->getResultCount());
        $this->assertInstanceOf(\DateTime::class, $found->getCreatedAt());
    }

    public function testUpdateSearchHistory(): void
    {
        $history = new SearchHistory();
        $history->setUserId('user-456');
        $history->setQuery('initial query');
        $history->setResultCount(10);

        $em = self::getEntityManager();
        $em->persist($history);
        $em->flush();

        $history->setResultCount(15);
        $em->flush();

        $found = $em->getRepository(SearchHistory::class)->find($history->getId());
        $this->assertSame(15, $found->getResultCount());
    }

    public function testDeleteSearchHistory(): void
    {
        $history = new SearchHistory();
        $history->setUserId('user-789');
        $history->setQuery('to delete');
        $history->setResultCount(0);

        $em = self::getEntityManager();
        $em->persist($history);
        $em->flush();

        $id = $history->getId();
        $em->remove($history);
        $em->flush();

        $found = $em->getRepository(SearchHistory::class)->find($id);
        $this->assertNull($found);
    }

    public function testSearchHistoryIdIsUuid(): void
    {
        $history = new SearchHistory();
        $history->setUserId('user-001');
        $history->setQuery('test');
        $history->setResultCount(1);

        $em = self::getEntityManager();
        $em->persist($history);
        $em->flush();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $history->getId());
    }
}
