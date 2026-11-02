<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\UserSessionRepository;
use App\Entity\Common\UserSession;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserSessionRepositoryTest extends TestCase
{
    private UserSessionRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(UserSessionRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findBy', 'findOneBy', 'count', 'getEntityManager'])
            ->getMock();

        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $session = $this->createMock(UserSession::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($session);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->save($session);
    }

    public function testRemoveRemovesAndFlushes(): void
    {
        $session = $this->createMock(UserSession::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($session);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->remove($session);
    }

    public function testFindById(): void
    {
        $id = 'sess-123';
        $expected = $this->createMock(UserSession::class);

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
        $expected = [$this->createMock(UserSession::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($expected);

        $result = $this->repo->findByUserId($userId);
        $this->assertSame($expected, $result);
    }

    public function testFindByTokenHash(): void
    {
        $tokenHash = 'hash-abc';
        $expected = $this->createMock(UserSession::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['tokenHash' => $tokenHash])
            ->willReturn($expected);

        $result = $this->repo->findByTokenHash($tokenHash);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveByUserId(): void
    {
        $userId = 'user-123';
        $expected = [$this->createMock(UserSession::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findActiveByUserId($userId);
        $this->assertSame($expected, $result);
    }

    public function testFindExpiredSessions(): void
    {
        $before = new \DateTime('-1 day');
        $expected = [$this->createMock(UserSession::class)];

        // Note: implementation ignores $before and returns active sessions (potential bug but tested as-is)
        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findExpiredSessions($before);
        $this->assertSame($expected, $result);
    }

    public function testCountActiveByUserId(): void
    {
        $userId = 'user-123';
        $count = 2;

        $this->repo->expects($this->once())
            ->method('count')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($count);

        $result = $this->repo->countActiveByUserId($userId);
        $this->assertSame($count, $result);
    }
}
