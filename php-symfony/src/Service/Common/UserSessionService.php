<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\UserSession;
use App\Repository\Common\UserSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\Exception\ORMException;

/**
 * Service for managing user sessions
 * Handles session creation, validation, refresh, and revocation
 */
class UserSessionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * Create a new user session
     */
    public function createSession(
        string $userId,
        string $tokenHash,
        \DateTime $expiresAt,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null
    ) {
        $session = new UserSession($this->generateId(), $userId, $tokenHash, $expiresAt);

        if ($ipAddress !== null) {
            $session->setIpAddress($ipAddress);
        }
        if ($userAgent !== null) {
            $session->setUserAgent($userAgent);
        }
        if ($deviceInfo !== null) {
            $session->setDeviceInfo($deviceInfo);
        }

        $this->persistAndLogSession($session, $userId);

        return $session;
    }

    private function persistAndLogSession(UserSession $session, string $userId): void
    {
        try {
            $this->em->persist($session);
            $this->em->flush();
            $this->logger->info('Session created', ['session_id' => $session->getId(), 'user_id' => $userId]);
        } catch (ORMException $e) {
            $this->logger->error('Failed to create session', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to create session', 0, $e);
        }
    }

    /**
     * Get session by ID
     */
    public function getSession(string $sessionId)
    {
        return $this->em->getRepository(UserSession::class)->find($sessionId);
    }

    /**
     * Get session by token hash
     */
    public function getSessionByToken(string $tokenHash)
    {
        return $this->em->getRepository(UserSession::class)
            ->findOneBy(['tokenHash' => $tokenHash]);
    }

    /**
     * Get all active sessions for a user
     */
    public function getUserSessions(string $userId)
    {
        return $this->em->getRepository(UserSession::class)
            ->findBy(['userId' => $userId, 'status' => 'ACTIVE']);
    }

    /**
     * Validate session (check if active and not expired)
     */
    public function validateSession(string $sessionId)
    {
        $session = $this->getSession($sessionId);
        return $session !== null && $session->isActive();
    }

    /**
     * Revoke a session
     */
    public function revokeSession(string $sessionId)
    {
        $session = $this->getSession($sessionId);
        if ($session === null) {
            return false;
        }

        $session->setStatus('REVOKED');
        $this->em->flush();

        $this->logger->info('Session revoked', [
            'session_id' => $sessionId,
            'user_id' => $session->getUserId(),
        ]);

        return true;
    }

    /**
     * Revoke all sessions for a user
     */
    public function revokeAllUserSessions(string $userId)
    {
        $sessions = $this->getUserSessions($userId);
        $count = count($sessions);

        foreach ($sessions as $session) {
            $session->setStatus('REVOKED');
        }

        $this->em->flush();

        $this->logger->info('All user sessions revoked', [
            'user_id' => $userId,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Update last activity time
     */
    public function updateLastActivity(string $sessionId)
    {
        $session = $this->getSession($sessionId);
        if ($session === null || !$session->isActive()) {
            return false;
        }

        $session->setLastActivityAt(new \DateTime());
        $this->em->flush();

        return true;
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions()
    {
        $now = new \DateTime();
        $sessions = $this->em->getRepository(UserSession::class)
            ->createQueryBuilder('s')
            ->where('s.expiresAt < :now')
            ->andWhere('s.status = :status')
            ->setParameter('now', $now)
            ->setParameter('status', 'ACTIVE')
            ->getQuery()
            ->getResult();

        foreach ($sessions as $session) {
            $session->setStatus('EXPIRED');
        }

        $this->em->flush();

        return count($sessions);
    }

    /**
     * Generate unique ID
     */
    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}