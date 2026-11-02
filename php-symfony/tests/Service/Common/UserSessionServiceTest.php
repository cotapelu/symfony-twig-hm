<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\UserSession;
use App\Repository\Common\UserSessionRepository;
use App\Service\Common\UserSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class UserSessionServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserSessionRepository $repository;
    private LoggerInterface $logger;
    private UserSessionService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(UserSessionRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($this->repository);
    }

    public function testCreateSession(): void
    {
        $userId = 'user-1';
        $tokenHash = 'hash123';
        $expiresAt = new \DateTime('+1 hour');
        $ip = '127.0.0.1';
        $ua = 'TestAgent';
        $device = ['os' => 'linux'];

        // Capture persisted entity
        $persisted = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (UserSession $s) use (&$persisted, $userId, $tokenHash, $expiresAt, $ip, $ua, $device) {
                $persisted = $s;
                return $persisted !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Session created', $this->arrayHasKey('session_id'));

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->createSession($userId, $tokenHash, $expiresAt, $ip, $ua, $device);

        $this->assertInstanceOf(UserSession::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($tokenHash, $result->getTokenHash());
        $this->assertSame($expiresAt, $result->getExpiresAt());
        $this->assertSame($ip, $result->getIpAddress());
        $this->assertSame($ua, $result->getUserAgent());
        $this->assertSame($device, $result->getDeviceInfo());
    }

    public function testGetSession(): void
    {
        $id = 'session-id';
        $session = $this->createMock(UserSession::class);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($session);

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->getSession($id);
        $this->assertSame($session, $result);
    }

    public function testGetSessionByToken(): void
    {
        $tokenHash = 'tokenhash';
        $session = $this->createMock(UserSession::class);
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['tokenHash' => $tokenHash])
            ->willReturn($session);

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->getSessionByToken($tokenHash);
        $this->assertSame($session, $result);
    }

    public function testGetUserSessions(): void
    {
        $userId = 'user-2';
        $sessions = [$this->createMock(UserSession::class), $this->createMock(UserSession::class)];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($sessions);

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->getUserSessions($userId);
        $this->assertSame($sessions, $result);
    }

    public function testValidateSessionReturnsTrueForActive(): void
    {
        $id = 'sess-id';
        $session = $this->createMock(UserSession::class);
        $session->method('isActive')->willReturn(true);
        $this->repository->expects($this->once())->method('find')->with($id)->willReturn($session);

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->validateSession($id);
        $this->assertTrue($result);
    }

    public function testValidateSessionReturnsFalseWhenNotFound(): void
    {
        $this->repository->expects($this->once())->method('find')->willReturn(null);
        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->validateSession('missing');
        $this->assertFalse($result);
    }

    public function testValidateSessionReturnsFalseWhenInactive(): void
    {
        $id = 'sess-id';
        $session = $this->createMock(UserSession::class);
        $session->method('isActive')->willReturn(false);
        $this->repository->expects($this->once())->method('find')->with($id)->willReturn($session);

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->validateSession($id);
        $this->assertFalse($result);
    }

    public function testRevokeSessionSuccess(): void
    {
        $sessionId = 'sess-id';
        $session = $this->createMock(UserSession::class);
        $session->method('getUserId')->willReturn('user-123');
        $session->expects($this->once())->method('setStatus')->with('REVOKED');

        $this->repository->expects($this->once())->method('find')->with($sessionId)->willReturn($session);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Session revoked', ['session_id' => $sessionId, 'user_id' => 'user-123']);

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->revokeSession($sessionId);
        $this->assertTrue($result);
    }

    public function testRevokeSessionNotFound(): void
    {
        $sessionId = 'missing';
        $this->repository->expects($this->once())->method('find')->with($sessionId)->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->revokeSession($sessionId);
        $this->assertFalse($result);
    }

    public function testRevokeAllUserSessions(): void
    {
        $userId = 'user-3';
        $sessions = [$this->createMock(UserSession::class), $this->createMock(UserSession::class)];
        foreach ($sessions as $s) {
            $s->expects($this->once())->method('setStatus')->with('REVOKED');
        }

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($sessions);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('All user sessions revoked', ['user_id' => $userId, 'count' => 2]);

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->revokeAllUserSessions($userId);
        $this->assertSame(2, $result);
    }

    public function testUpdateLastActivitySuccess(): void
    {
        $sessionId = 'sess-id';
        $session = $this->createMock(UserSession::class);
        $session->method('isActive')->willReturn(true);
        $session->expects($this->once())->method('setLastActivityAt')->with($this->isInstanceOf(\DateTime::class));

        $this->repository->expects($this->once())->method('find')->with($sessionId)->willReturn($session);
        $this->em->expects($this->once())->method('flush');

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->updateLastActivity($sessionId);
        $this->assertTrue($result);
    }

    public function testUpdateLastActivityFailsWhenNotFound(): void
    {
        $this->repository->expects($this->once())->method('find')->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->updateLastActivity('missing');
        $this->assertFalse($result);
    }

    public function testUpdateLastActivityFailsWhenInactive(): void
    {
        $session = $this->createMock(UserSession::class);
        $session->method('isActive')->willReturn(false);
        $this->repository->expects($this->once())->method('find')->willReturn($session);
        $this->em->expects($this->never())->method('flush');

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->updateLastActivity('sess-id');
        $this->assertFalse($result);
    }

    public function testCleanupExpiredSessions(): void
    {
        $now = new \DateTime();
        $expired1 = $this->createMock(UserSession::class);
        $expired2 = $this->createMock(UserSession::class);
        $expiredSessions = [$expired1, $expired2];
        foreach ($expiredSessions as $s) {
            $s->expects($this->once())->method('setStatus')->with('EXPIRED');
        }

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('where')->with('s.expiresAt < :now')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('s.status = :status')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn($expiredSessions);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($qb);
        $this->em->expects($this->once())->method('flush');

        $this->service = new UserSessionService($this->em, $this->logger);
        $result = $this->service->cleanupExpiredSessions();
        $this->assertSame(2, $result);
    }
}
