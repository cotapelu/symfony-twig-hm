<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\NotificationChannelRepository;
use App\Entity\Common\NotificationChannel;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationChannelRepositoryTest extends TestCase
{
    private NotificationChannelRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(NotificationChannelRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findBy', 'findOneBy', 'count', 'getEntityManager'])
            ->getMock();

        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $channel = $this->createMock(NotificationChannel::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($channel);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->save($channel);
    }

    public function testRemoveRemovesAndFlushes(): void
    {
        $channel = $this->createMock(NotificationChannel::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($channel);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->remove($channel);
    }

    public function testFindById(): void
    {
        $id = 'channel-123';
        $expected = $this->createMock(NotificationChannel::class);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expected);

        $result = $this->repo->findById($id);
        $this->assertSame($expected, $result);
    }

    public function testFindByUserId(): void
    {
        $userId = 'user-123';
        $expected = [$this->createMock(NotificationChannel::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($expected);

        $result = $this->repo->findByUserId($userId);
        $this->assertSame($expected, $result);
    }

    public function testFindByUserAndChannel(): void
    {
        $userId = 'user-123';
        $channel = 'email';
        $expected = $this->createMock(NotificationChannel::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn($expected);

        $result = $this->repo->findByUserAndChannel($userId, $channel);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveByUserId(): void
    {
        $userId = 'user-123';
        $expected = [$this->createMock(NotificationChannel::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findActiveByUserId($userId);
        $this->assertSame($expected, $result);
    }

    public function testFindByChannel(): void
    {
        $channel = 'sms';
        $expected = [$this->createMock(NotificationChannel::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['channel' => $channel, 'status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findByChannel($channel);
        $this->assertSame($expected, $result);
    }
}
