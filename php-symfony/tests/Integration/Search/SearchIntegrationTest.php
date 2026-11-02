<?php

declare(strict_types=1);

namespace App\Tests\Integration\Search;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Search\Service\SearchService;
use App\Search\Entity\SearchHistory;
use App\Search\Entity\SearchResult;

#[AllowMockObjectsWithoutExpectations]
class SearchIntegrationTest extends DatabaseIntegrationTestCase
{
    private SearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $em = self::getEntityManager();
        $this->searchService = new SearchService($em);
    }

    public function testSaveSearchHistory(): void
    {
        $userId = 'user-123';
        $query = 'symfony integration testing';

        $history = $this->searchService->saveSearchHistory($userId, $query, 42);

        $this->assertInstanceOf(SearchHistory::class, $history);
        $this->assertNotNull($history->getId());
        $this->assertSame($userId, $history->getUserId());
        $this->assertSame($query, $history->getQuery());
        $this->assertSame(42, $history->getResultCount());
    }

    public function testGetRecentSearchesReturnsOnlyMatchingUser(): void
    {
        $userId1 = 'user-A';
        $userId2 = 'user-B';

        // Create histories for two users
        $this->searchService->saveSearchHistory($userId1, 'query1', 1);
        $this->searchService->saveSearchHistory($userId2, 'query2', 2);
        $this->searchService->saveSearchHistory($userId1, 'query3', 3);

        $recent = $this->searchService->getRecentSearches($userId1, 10);
        $this->assertCount(2, $recent);
        foreach ($recent as $h) {
            $this->assertSame($userId1, $h->getUserId());
        }
        // Ensure ordered by createdAt DESC (most recent first)
        $this->assertGreaterThanOrEqual(
            $recent[0]->getCreatedAt()->getTimestamp(),
            $recent[1]->getCreatedAt()->getTimestamp()
        );
    }

    public function testGetTrendingQueries(): void
    {
        $query = 'trending query';
        // Create multiple searches with same query in last 24h
        for ($i = 0; $i < 5; $i++) {
            $this->searchService->saveSearchHistory("user-$i", $query, 10);
        }

        $trending = $this->searchService->getTrendingQueries(5);
        $this->assertNotEmpty($trending);
        $this->assertContains($query, $trending);
    }

    public function testSearchByUserId(): void
    {
        $userId = 'user-srch';
        $em = self::getEntityManager();

        // Create search results manually to have control
        $r1 = new SearchResult(); $r1->setUserId($userId); $r1->setQuery('python'); $r1->setEntityType('Article'); $r1->setEntityId('art-1'); $r1->setScore(0.9);
        $r2 = new SearchResult(); $r2->setUserId($userId); $r2->setQuery('go'); $r2->setEntityType('Article'); $r2->setEntityId('art-2'); $r2->setScore(0.8);
        $r3 = new SearchResult(); $r3->setUserId('other-user'); $r3->setQuery('java'); $r3->setEntityType('Article'); $r3->setEntityId('art-3'); $r3->setScore(0.7);

        foreach ([$r1, $r2, $r3] as $r) {
            $em->persist($r);
        }
        $em->flush();

        $results = $this->searchService->search($userId, '', null, 20);
        $this->assertCount(2, $results);
        foreach ($results as $r) {
            $this->assertSame($userId, $r->getUserId());
        }
    }

    public function testSearchFiltersByEntityType(): void
    {
        $userId = 'user-filter';
        $em = self::getEntityManager();

        $r1 = new SearchResult(); $r1->setUserId($userId); $r1->setQuery('q1'); $r1->setEntityType('Article'); $r1->setEntityId('a1'); $r1->setScore(0.5);
        $r2 = new SearchResult(); $r2->setUserId($userId); $r2->setQuery('q2'); $r2->setEntityType('User'); $r2->setEntityId('u1'); $r2->setScore(0.6);
        $r3 = new SearchResult(); $r3->setUserId($userId); $r3->setQuery('q3'); $r3->setEntityType('Article'); $r3->setEntityId('a2'); $r3->setScore(0.7);

        foreach ([$r1, $r2, $r3] as $r) {
            $em->persist($r);
        }
        $em->flush();

        $articles = $this->searchService->search($userId, '', 'Article', 20);
        $this->assertCount(2, $articles);
        foreach ($articles as $a) {
            $this->assertSame('Article', $a->getEntityType());
        }
    }

    public function testSearchRespectsLimit(): void
    {
        $userId = 'user-limit2';
        $em = self::getEntityManager();
        for ($i = 0; $i < 10; $i++) {
            $r = new SearchResult();
            $r->setUserId($userId);
            $r->setQuery("query $i");
            $r->setEntityType('News');
            $r->setEntityId("news-$i");
            $r->setScore(10 - $i);
            $em->persist($r);
        }
        $em->flush();

        $limited = $this->searchService->search($userId, '', null, 5);
        $this->assertCount(5, $limited);
    }
}
