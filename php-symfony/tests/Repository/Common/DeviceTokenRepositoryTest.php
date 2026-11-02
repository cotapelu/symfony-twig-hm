<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\DeviceTokenRepository;
use App\Entity\Common\DeviceToken;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeviceTokenRepositoryTest extends TestCase
{
    private DeviceTokenRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(DeviceTokenRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testDeleteByUserIdBuildsCorrectQueryAndExecutes(): void
    {
        $userId = 'user-123';
        $affected = 2;

        $this->qb->expects($this->once())
            ->method('delete')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('t.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn($affected);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($this->qb);

        $result = $this->repo->deleteByUserId($userId);
        $this->assertSame($affected, $result);
    }

    public function testDeleteByTokenBuildsCorrectQueryAndExecutes(): void
    {
        $token = 'device-token-abc';
        $affected = 1;

        $this->qb->expects($this->once())
            ->method('delete')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('t.token = :token')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('token', $token)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn($affected);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($this->qb);

        $result = $this->repo->deleteByToken($token);
        $this->assertSame($affected, $result);
    }
}
