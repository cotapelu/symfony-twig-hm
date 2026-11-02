<?php declare(strict_types=1);

namespace App\Tests\Unit\Core\Repository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Repository\OAuthProviderRepository;
use App\Core\Entity\OAuthProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OAuthProviderRepositoryTest extends TestCase
{
    private OAuthProviderRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(OAuthProviderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder', 'getEntityManager'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testFindByUserIdBuildsCorrectQuery(): void
    {
        $userId = 42;
        $capturedParams = [];

        $this->qb->expects($this->once())
            ->method('where')
            ->with('p.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$capturedParams) {
                $capturedParams[$name] = $value;
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($this->qb);

        $result = $this->repo->findByUserId($userId);
        $this->assertIsArray($result);
        $this->assertSame($userId, $capturedParams['userId']);
    }

    public function testFindByProviderBuildsCorrectQuery(): void
    {
        $provider = 'google';
        $capturedParams = [];

        $this->qb->expects($this->once())
            ->method('where')
            ->with('p.provider = :provider')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$capturedParams) {
                $capturedParams[$name] = $value;
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($this->qb);

        $result = $this->repo->findByProvider($provider);
        $this->assertIsArray($result);
        $this->assertSame($provider, $capturedParams['provider']);
    }

    public function testFindByProviderAndUserIdAppliesBothFilters(): void
    {
        $provider = 'facebook';
        $userId = 7;
        $expected = $this->createMock(OAuthProvider::class);
        $whereClauses = [];
        $andWhereClauses = [];
        $params = [];

        $this->qb->expects($this->once())
            ->method('where')
            ->willReturnCallback(function ($predicate) use (&$whereClauses) {
                $whereClauses[] = $predicate;
                return $this->qb;
            });
        $this->qb->expects($this->any())
            ->method('andWhere')
            ->willReturnCallback(function ($predicate) use (&$andWhereClauses) {
                $andWhereClauses[] = $predicate;
                return $this->qb;
            });
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$params) {
                $params[$name] = $value;
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($expected);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($this->qb);

        $result = $this->repo->findByProviderAndUserId($provider, $userId);
        $this->assertSame($expected, $result);
        $this->assertCount(1, $whereClauses);
        $this->assertStringContainsString('p.provider = :provider', $whereClauses[0]);
        $this->assertCount(1, $andWhereClauses);
        $this->assertStringContainsString('p.userId = :userId', $andWhereClauses[0]);
        $this->assertSame($provider, $params['provider']);
        $this->assertSame($userId, $params['userId']);
    }

    public function testFindByProviderIdAppliesBothFilters(): void
    {
        $provider = 'github';
        $providerId = 'gh_12345';
        $expected = $this->createMock(OAuthProvider::class);
        $whereClauses = [];
        $andWhereClauses = [];
        $params = [];

        $this->qb->expects($this->once())
            ->method('where')
            ->willReturnCallback(function ($predicate) use (&$whereClauses) {
                $whereClauses[] = $predicate;
                return $this->qb;
            });
        $this->qb->expects($this->any())
            ->method('andWhere')
            ->willReturnCallback(function ($predicate) use (&$andWhereClauses) {
                $andWhereClauses[] = $predicate;
                return $this->qb;
            });
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$params) {
                $params[$name] = $value;
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($expected);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($this->qb);

        $result = $this->repo->findByProviderId($provider, $providerId);
        $this->assertSame($expected, $result);
        $this->assertCount(1, $whereClauses);
        $this->assertStringContainsString('p.provider = :provider', $whereClauses[0]);
        $this->assertCount(1, $andWhereClauses);
        $this->assertStringContainsString('p.providerId = :providerId', $andWhereClauses[0]);
        $this->assertSame($provider, $params['provider']);
        $this->assertSame($providerId, $params['providerId']);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $provider = $this->createMock(OAuthProvider::class);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($provider)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repo->expects($this->exactly(2))
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->repo->save($provider);
        $this->addToAssertionCount(1);
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $provider = $this->createMock(OAuthProvider::class);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($provider)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repo->expects($this->exactly(2))
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->repo->delete($provider);
        $this->addToAssertionCount(1);
    }
}