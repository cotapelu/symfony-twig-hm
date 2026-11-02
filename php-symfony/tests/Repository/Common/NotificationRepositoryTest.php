<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\NotificationRepository;
use App\Entity\Common\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationRepositoryTest extends TestCase
{
    private NotificationRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(NotificationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testFindUnreadNotificationsBuildsCorrectQuery(): void
    {
        $userId = 'user-1';

        $this->qb->expects($this->once())
            ->method('where')
            ->with('n.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('n.readAt IS NULL')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('n.createdAt', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($userId) {
                if ($name === 'userId') {
                    $this->assertSame($userId, $value);
                }
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
            ->with('n')
            ->willReturn($this->qb);

        $result = $this->repo->findUnreadNotifications($userId);
        $this->assertIsArray($result);
    }

    public function testFindReadNotificationsBuildsCorrectQuery(): void
    {
        $userId = 'user-2';

        $this->qb->expects($this->once())
            ->method('where')
            ->with('n.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('n.readAt IS NOT NULL')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('n.readAt', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($userId) {
                $this->assertSame('userId', $name);
                $this->assertSame($userId, $value);
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
            ->with('n')
            ->willReturn($this->qb);

        $result = $this->repo->findReadNotifications($userId);
        $this->assertIsArray($result);
    }

    public function testCountUnreadNotificationsBuildsCorrectQuery(): void
    {
        $userId = 'user-3';
        $expectedCount = 5;

        $this->qb->expects($this->once())
            ->method('select')
            ->with('COUNT(n.id)')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('n.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('n.readAt IS NULL')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($userId) {
                $this->assertSame('userId', $name);
                $this->assertSame($userId, $value);
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($expectedCount);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('n')
            ->willReturn($this->qb);

        $result = $this->repo->countUnreadNotifications($userId);
        $this->assertSame($expectedCount, $result);
    }

    public function testMarkAsReadBuildsCorrectUpdateQuery(): void
    {
        $id = 'notif-1';

        $this->qb->expects($this->once())
            ->method('update')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('set')
            ->with('n.readAt', ':now')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('n.id = :id')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($id) {
                if ($name === 'id') {
                    $this->assertSame($id, $value);
                } else {
                    $this->assertSame('now', $name);
                    $this->assertInstanceOf(\DateTime::class, $value);
                }
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn(1);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('n')
            ->willReturn($this->qb);

        $this->repo->markAsRead($id);
        $this->addToAssertionCount(1);
    }

    public function testMarkAllAsReadBuildsCorrectUpdateQuery(): void
    {
        $userId = 'user-4';
        $affectedRows = 3;

        $this->qb->expects($this->once())
            ->method('update')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('set')
            ->with('n.readAt', ':now')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('n.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('n.readAt IS NULL')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($userId) {
                if ($name === 'userId') {
                    $this->assertSame($userId, $value);
                } else {
                    $this->assertSame('now', $name);
                    $this->assertInstanceOf(\DateTime::class, $value);
                }
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn($affectedRows);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('n')
            ->willReturn($this->qb);

        $result = $this->repo->markAllAsRead($userId);
        $this->assertSame($affectedRows, $result);
    }
}
