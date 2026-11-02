<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\UserSession;
use App\Service\Common\UserSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserSessionServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private UserSessionService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new UserSessionService($this->em, $this->logger);
    }

    public function testCreateSession(): void
    {
        $userId = 'user_123';
        $tokenHash = 'hash_abc';
        $expiresAt = new \DateTime('+1 hour');
        $ipAddress = '127.0.0.1';
        $userAgent = 'Mozilla/5.0';
        $deviceInfo = ['os' => 'Linux', 'browser' => 'Chrome'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (UserSession $s) use (
                $userId, $tokenHash, $expiresAt, $ipAddress, $userAgent, $deviceInfo
            ) {
                return $s->getUserId() === $userId
                    && $s->getTokenHash() === $tokenHash
                    && $s->getExpiresAt() === $expiresAt
                    && $s->getIpAddress() === $ipAddress
                    && $s->getUserAgent() === $userAgent
                    && $s->getDeviceInfo() === $deviceInfo;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Session created', $this->anything());

        $result = $this->service->createSession($userId, $tokenHash, $expiresAt, $ipAddress, $userAgent, $deviceInfo);
        $this->assertInstanceOf(UserSession::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($tokenHash, $result->getTokenHash());
        $this->assertSame($expiresAt, $result->getExpiresAt());
    }

    public function testCreateSessionMinimal(): void
    {
        $userId = 'user_123';
        $tokenHash = 'hash_abc';
        $expiresAt = new \DateTime('+1 hour');

        $repo = $this->createMock(ObjectRepository::class);
        $this->em->method('getRepository')->willReturn($repo);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createSession($userId, $tokenHash, $expiresAt);
        $this->assertInstanceOf(UserSession::class, $result);
        $this->assertNull($result->getIpAddress());
        $this->assertNull($result->getUserAgent());
        $this->assertNull($result->getDeviceInfo());
    }

    public function testGetSession(): void
    {
        $id = 'session_123';
        $session = new UserSession($id, 'user', 'hash', new \DateTime());

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($session);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($repo);

        $result = $this->service->getSession($id);
        $this->assertSame($session, $result);
    }

    public function testGetSessionByToken(): void
    {
        $tokenHash = 'hash_abc';
        $session = new UserSession('id', 'user', $tokenHash, new \DateTime());

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['tokenHash' => $tokenHash])
            ->willReturn($session);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getSessionByToken($tokenHash);
        $this->assertSame($session, $result);
    }

    public function testGetUserSessions(): void
    {
        $userId = 'user_123';
        $sessions = [
            new UserSession('id1', $userId, 'hash1', new \DateTime()),
            new UserSession('id2', $userId, 'hash2', new \DateTime()),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($sessions);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUserSessions($userId);
        $this->assertCount(2, $result);
    }

    public function testValidateSessionActive(): void
    {
        $session = new UserSession('id', 'user', 'hash', new \DateTime());
        $session->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn($session);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->validateSession('id');
        $this->assertTrue($result);
    }

    public function testValidateSessionInactive(): void
    {
        $session = new UserSession('id', 'user', 'hash', new \DateTime());
        $session->setStatus('INACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn($session);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->validateSession('id');
        $this->assertFalse($result);
    }

    public function testValidateSessionNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->validateSession('nonexistent');
        $this->assertFalse($result);
    }

    public function testRevokeSession(): void
    {
        $sessionId = 'session_123';
        $session = new UserSession($sessionId, 'user', 'hash', new \DateTime());
        $session->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($sessionId)
            ->willReturn($session);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Session revoked', $this->anything());

        $result = $this->service->revokeSession($sessionId);
        $this->assertTrue($result);
        $this->assertSame('REVOKED', $session->getStatus());
    }

    public function testRevokeSessionNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->revokeSession('nonexistent');
        $this->assertFalse($result);
    }

    public function testRevokeAllUserSessions(): void
    {
        $userId = 'user_123';
        $sessions = [
            new UserSession('id1', $userId, 'hash1', new \DateTime()),
            new UserSession('id2', $userId, 'hash2', new \DateTime()),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($sessions);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        foreach ($sessions as $s) {
            $this->em->expects($this->once())
                ->method('flush');
        }

        $result = $this->service->revokeAllUserSessions($userId);
        $this->assertSame(2, $result);
        foreach ($sessions as $s) {
            $this->assertSame('REVOKED', $s->getStatus());
        }
    }

    public function testExtendSessionExpiry(): void
    {
        $sessionId = 'session_123';
        $session = new UserSession($sessionId, 'user', 'hash', new \DateTime('-1 hour'));
        $newExpiresAt = new \DateTime('+2 hours');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($sessionId)
            ->willReturn($session);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->extendSessionExpiry($sessionId, $newExpiresAt);
        $this->assertSame($session, $result);
        $this->assertSame($newExpiresAt, $result->getExpiresAt());
    }

    public function testCountActiveByUser(): void
    {
        $userId = 'user_123';
        $count = 3;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(s.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('s.userId = :userId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('s.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $count);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($repo);

        $result = $this->service->countActiveByUser($userId);
        $this->assertSame($count, $result);
    }

    public function testGetExpiredSessions(): void
    {
        $expiredBefore = new \DateTime('-1 day');
        $expiredSessions = [
            new UserSession('id1', 'user', 'hash1', new \DateTime()),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('s.expiresAt < :expiredBefore')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('expiredBefore', $expiredBefore)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expiredSessions);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($repo);

        $result = $this->service->getExpiredSessions($expiredBefore);
        $this->assertCount(1, $result);
    }
}
