<?php declare(strict_types=1);

namespace App\Tests\Unit\Search\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Search\Service\SearchService;
use App\Search\Entity\SearchResult;
use App\Search\Entity\SearchHistory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Result;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SearchServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private QueryBuilder $qb;
    private Query $query;
    private SearchService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        $this->em->method('createQueryBuilder')
            ->willReturn($this->qb);

        // Fluent stubs for QueryBuilder (default responses)
        $this->qb->method('select')->willReturnSelf();
        $this->qb->method('from')->willReturnSelf();
        $this->qb->method('where')->willReturnSelf();
        $this->qb->method('andWhere')->willReturnSelf();
        $this->qb->method('setParameter')->willReturnSelf();
        $this->qb->method('orderBy')->willReturnSelf();
        $this->qb->method('setMaxResults')->willReturnSelf();
        $this->qb->method('getQuery')->willReturn($this->query);

        $this->service = new SearchService($this->em);
    }

    public function testSearchBuildsCorrectQueryWithoutEntityType(): void
    {
        $userId = 'user_123';
        $queryStr = 'test query';
        $limit = 10;

        // Capture setParameter calls
        $setParameterCalls = [];
        $this->qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParameterCalls) {
                $setParameterCalls[] = ['name' => $name, 'value' => $value];
                return $this->qb;
            });

        // Capture andWhere call
        $andWhereCalls = [];
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->willReturnCallback(function ($predicate) use (&$andWhereCalls) {
                $andWhereCalls[] = $predicate;
                return $this->qb;
            });

        $this->qb->expects($this->once())
            ->method('select')
            ->with('sr')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('from')
            ->with(SearchResult::class, 'sr')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('sr.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('sr.score', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $expectedResults = [$this->createSearchResultMock($userId, $queryStr)];
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedResults);

        $result = $this->service->search($userId, $queryStr, null, $limit);

        $this->assertSame($expectedResults, $result);
        $this->assertCount(2, $setParameterCalls);
        $this->assertSame('userId', $setParameterCalls[0]['name']);
        $this->assertSame($userId, $setParameterCalls[0]['value']);
        $this->assertSame('query', $setParameterCalls[1]['name']);
        $this->assertSame('%' . $queryStr . '%', $setParameterCalls[1]['value']);
        $this->assertCount(1, $andWhereCalls);
        $this->assertSame('sr.query LIKE :query', $andWhereCalls[0]);
    }

    public function testSearchAppliesEntityTypeFilter(): void
    {
        $userId = 'user_123';
        $queryStr = 'test';
        $entityType = 'Article';
        $limit = 5;

        $setParameterCalls = [];
        $this->qb->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParameterCalls) {
                $setParameterCalls[] = ['name' => $name, 'value' => $value];
                return $this->qb;
            });

        $andWhereCalls = [];
        $this->qb->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function ($predicate) use (&$andWhereCalls) {
                $andWhereCalls[] = $predicate;
                return $this->qb;
            });

        $this->qb->expects($this->once())
            ->method('select')
            ->with('sr')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('from')
            ->with(SearchResult::class, 'sr')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('sr.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('sr.score', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $this->query->method('getResult')->willReturn([]);

        $result = $this->service->search($userId, $queryStr, $entityType, $limit);
        $this->assertIsArray($result);
        $this->assertCount(3, $setParameterCalls);
        $this->assertSame('userId', $setParameterCalls[0]['name']);
        $this->assertSame($userId, $setParameterCalls[0]['value']);
        $this->assertSame('query', $setParameterCalls[1]['name']);
        $this->assertSame('%' . $queryStr . '%', $setParameterCalls[1]['value']);
        $this->assertSame('entityType', $setParameterCalls[2]['name']);
        $this->assertSame($entityType, $setParameterCalls[2]['value']);
        $this->assertCount(2, $andWhereCalls);
        $this->assertSame('sr.query LIKE :query', $andWhereCalls[0]);
        $this->assertSame('sr.entityType = :entityType', $andWhereCalls[1]);
    }

    public function testSearchLimitsToMaxWhenLimitExceeded(): void
    {
        $userId = 'u';
        $queryStr = 'q';
        $limit = 200; // exceeds MAX_LIMIT 100

        $setParameterCalls = [];
        $this->qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParameterCalls) {
                $setParameterCalls[] = ['name' => $name, 'value' => $value];
                return $this->qb;
            });

        $this->qb->expects($this->once())
            ->method('select')
            ->with('sr')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('from')
            ->with(SearchResult::class, 'sr')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('sr.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('sr.query LIKE :query')
            ->willReturnSelf(); // using stub since we don't capture andWhere for this test
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('sr.score', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with(100) // capped at MAX_LIMIT
            ->willReturnSelf();

        $this->query->method('getResult')->willReturn([]);
        $this->service->search($userId, $queryStr, null, $limit);
        $this->assertCount(2, $setParameterCalls);
    }

    public function testSaveSearchHistoryPersistsAndFlushes(): void
    {
        $userId = 'user_123';
        $query = 'test query';
        $resultCount = 42;

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (SearchHistory $history) use ($userId, $query, $resultCount) {
                return $history->getUserId() === $userId
                    && $history->getQuery() === $query
                    && $history->getResultCount() === $resultCount;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->saveSearchHistory($userId, $query, $resultCount);
        $this->assertInstanceOf(SearchHistory::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($query, $result->getQuery());
        $this->assertSame($resultCount, $result->getResultCount());
    }

    public function testGetRecentSearchesBuildsQuery(): void
    {
        $userId = 'user_123';
        $limit = 5;

        $setParameterCalls = [];
        $this->qb->expects($this->exactly(1))
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParameterCalls) {
                $setParameterCalls[] = ['name' => $name, 'value' => $value];
                return $this->qb;
            });

        $this->qb->expects($this->once())
            ->method('select')
            ->with('sh')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('from')
            ->with(SearchHistory::class, 'sh')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('sh.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('sh.createdAt', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $history1 = $this->createSearchHistoryMock($userId, 'query1');
        $history2 = $this->createSearchHistoryMock($userId, 'query2');
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([$history1, $history2]);

        $result = $this->service->getRecentSearches($userId, $limit);
        $this->assertCount(2, $result);
        $this->assertSame($history1, $result[0]);
        $this->assertSame($history2, $result[1]);
        $this->assertCount(1, $setParameterCalls);
        $this->assertSame('userId', $setParameterCalls[0]['name']);
        $this->assertSame($userId, $setParameterCalls[0]['value']);
    }

    public function testGetTrendingQueriesBuildsQuery(): void
    {
        $limit = 10;

        $setParameterCalls = [];
        $this->qb->expects($this->exactly(1))
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParameterCalls) {
                $setParameterCalls[] = ['name' => $name, 'value' => $value];
                return $this->qb;
            });

        $this->qb->expects($this->once())
            ->method('select')
            ->with('sh.query, COUNT(sh.id) as count')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('from')
            ->with(SearchHistory::class, 'sh')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('sh.createdAt > :date')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('groupBy')
            ->with('sh.query')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('count', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([['query' => 'foo'], ['query' => 'bar']]);

        $result = $this->service->getTrendingQueries($limit);
        $this->assertSame(['foo', 'bar'], $result);
        $this->assertCount(1, $setParameterCalls);
        $this->assertSame('date', $setParameterCalls[0]['name']);
        $this->assertInstanceOf(\DateTime::class, $setParameterCalls[0]['value']);
    }

    private function createSearchResultMock(string $userId, string $query): SearchResult
    {
        $result = new SearchResult();
        $result->setUserId($userId);
        $result->setQuery($query);
        $result->setEntityType('test');
        $result->setEntityId('entity_123');
        $result->setScore(1.0);
        return $result;
    }

    private function createSearchHistoryMock(string $userId, string $query): SearchHistory
    {
        $history = new SearchHistory();
        $history->setUserId($userId);
        $history->setQuery($query);
        $history->setResultCount(10);
        return $history;
    }
}
