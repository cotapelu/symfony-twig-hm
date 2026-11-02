<?php

declare(strict_types=1);

namespace App\Tests\Integration\Core;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Core\Entity\UserSession;
use App\Core\Entity\User as CoreUser;

#[AllowMockObjectsWithoutExpectations]
class UserSessionIntegrationTest extends DatabaseIntegrationTestCase
{
    private CoreUser $user;

    private function createUser(): CoreUser
    {
        $user = new CoreUser();
        $user->setEmail('test@example.com');
        // Note: passwordHash may need to be set; but for test we can set a dummy hash
        $user->setPasswordHash('dummy');
        $user->setStatus(CoreUser::STATUS_ACTIVE);

        $em = self::getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
    }

    protected function tearDown(): void
    {
        // Clean up
        $em = self::getEntityManager();
        // Remove sessions if any
        $sessions = $em->getRepository(UserSession::class)->findBy(['user' => $this->user]);
        foreach ($sessions as $s) {
            $em->remove($s);
        }
        $em->remove($this->user);
        $em->flush();
        parent::tearDown();
    }

    public function testCreateSession(): void
    {
        $token = 'token-' . bin2hex(random_bytes(8));
        $expiresAt = new \DateTime('+1 hour');

        $session = new UserSession(
            user: $this->user,
            token: $token,
            expiresAt: $expiresAt
        );

        $em = self::getEntityManager();
        $em->persist($session);
        $em->flush();

        $this->assertNotNull($session->getId());
        $this->assertSame($token, $session->getToken());
        $this->assertSame($this->user->getId(), $session->getUser()->getId());
        $this->assertNull($session->getRefreshToken());
        $this->assertSame($expiresAt, $session->getExpiresAt());
    }

    public function testFindByUser(): void
    {
        $token1 = 'token1-' . bin2hex(random_bytes(8));
        $token2 = 'token2-' . bin2hex(random_bytes(8));
        $expires = new \DateTime('+1 hour');

        $session1 = new UserSession($this->user, $token1, $expires);
        $session2 = new UserSession($this->user, $token2, $expires);

        $em = self::getEntityManager();
        $em->persist($session1);
        $em->persist($session2);
        $em->flush();

        $repo = $em->getRepository(UserSession::class);
        $sessions = $repo->findBy(['user' => $this->user->getId()]);

        $this->assertCount(2, $sessions);
        $tokens = array_map(fn($s) => $s->getToken(), $sessions);
        $this->assertContains($token1, $tokens);
        $this->assertContains($token2, $tokens);
    }

    public function testSetRefreshToken(): void
    {
        $token = 'token-' . bin2hex(random_bytes(8));
        $refreshToken = 'refresh-' . bin2hex(random_bytes(8));
        $expiresAt = new \DateTime('+1 hour');

        $session = new UserSession($this->user, $token, $expiresAt);
        $session->setRefreshToken($refreshToken);

        $em = self::getEntityManager();
        $em->persist($session);
        $em->flush();

        $found = $em->getRepository(UserSession::class)->find($session->getId());
        $this->assertNotNull($found);
        $this->assertSame($refreshToken, $found->getRefreshToken());
    }
}
