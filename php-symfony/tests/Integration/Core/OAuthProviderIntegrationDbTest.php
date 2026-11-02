<?php

declare(strict_types=1);

namespace App\Tests\Integration\Core;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Core\Entity\User;
use App\Core\Entity\OAuthProvider;

#[AllowMockObjectsWithoutExpectations]
class OAuthProviderIntegrationDbTest extends DatabaseIntegrationTestCase
{
    private ?User $user = null;

    private function createUser(): User
    {
        $email = 'test-' . bin2hex(random_bytes(8)) . '@example.com';
        $user = new User();
        $user->setEmail($email);
        $user->setPasswordHash(password_hash('password', PASSWORD_BCRYPT));
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
        $em = self::getEntityManager();
        // Remove oauth providers first
        $providers = $em->getRepository(OAuthProvider::class)->findBy(['user' => $this->user]);
        foreach ($providers as $p) {
            $em->remove($p);
        }
        $em->remove($this->user);
        $em->flush();
        parent::tearDown();
    }

    public function testCreateAndRetrieveOAuthProvider(): void
    {
        $provider = new OAuthProvider($this->user, 'google', '1234567890');
        $provider->setAccessToken('access-token-xyz');
        $provider->setRefreshToken('refresh-token-xyz');
        $provider->setExpiresAt(new \DateTime('+1 hour'));

        $em = self::getEntityManager();
        $em->persist($provider);
        $em->flush();

        $this->assertNotNull($provider->getId());

        $repo = $em->getRepository(OAuthProvider::class);
        $found = $repo->find($provider->getId());

        $this->assertNotNull($found);
        $this->assertSame($this->user->getId(), $found->getUser()->getId());
        $this->assertSame('google', $found->getProvider());
        $this->assertSame('1234567890', $found->getProviderUserId());
        $this->assertSame('access-token-xyz', $found->getAccessToken());
        $this->assertSame('refresh-token-xyz', $found->getRefreshToken());
        $this->assertInstanceOf(\DateTime::class, $found->getExpiresAt());
    }

    public function testFindByUser(): void
    {
        $p1 = new OAuthProvider($this->user, 'facebook', 'fb-111');
        $p1->setAccessToken('token1');
        $p1->setExpiresAt(new \DateTime('+2 hours'));

        $p2 = new OAuthProvider($this->user, 'google', 'google-222');
        $p2->setAccessToken('token2');
        $p2->setExpiresAt(new \DateTime('+3 hours'));

        $em = self::getEntityManager();
        $em->persist($p1);
        $em->persist($p2);
        $em->flush();

        $repo = $em->getRepository(OAuthProvider::class);
        $byUser = $repo->findBy(['user' => $this->user->getId()]);

        $this->assertCount(2, $byUser);
        $providers = array_map(fn($p) => $p->getProvider(), $byUser);
        $this->assertContains('facebook', $providers);
        $this->assertContains('google', $providers);
    }

    public function testDeleteOAuthProvider(): void
    {
        $provider = new OAuthProvider($this->user, 'github', 'gh-999');
        $provider->setAccessToken('token-gh');
        $provider->setExpiresAt(new \DateTime('+1 day'));

        $em = self::getEntityManager();
        $em->persist($provider);
        $em->flush();

        $id = $provider->getId();

        $em->remove($provider);
        $em->flush();

        $deleted = $em->getRepository(OAuthProvider::class)->find($id);
        $this->assertNull($deleted);
    }
}
