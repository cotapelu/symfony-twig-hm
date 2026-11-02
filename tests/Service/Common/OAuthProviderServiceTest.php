<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\OAuthProvider;
use App\Service\Common\OAuthProviderService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OAuthProviderServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private OAuthProviderService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new OAuthProviderService($this->em, $this->logger);
    }

    public function testLinkProviderCreatesNew(): void
    {
        $userId = 'user_123';
        $provider = 'google';
        $providerUserId = 'google_456';
        $accessToken = 'access_token_abc';
        $refreshToken = 'refresh_token_xyz';
        $expiresAt = new \DateTime('+1 hour');
        $scope = 'profile email';
        $metadata = ['picture' => 'url'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with([
                'userId' => $userId,
                'provider' => $provider,
                'providerUserId' => $providerUserId,
            ])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(OAuthProvider::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (OAuthProvider $o) use (
                $userId, $provider, $providerUserId, $accessToken, $refreshToken, $expiresAt, $scope, $metadata
            ) {
                return $o->getUserId() === $userId
                    && $o->getProvider() === $provider
                    && $o->getProviderUserId() === $providerUserId
                    && $o->getAccessToken() === $accessToken
                    && $o->getRefreshToken() === $refreshToken
                    && $o->getTokenExpiresAt() === $expiresAt
                    && $o->getScope() === $scope
                    && $o->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('OAuth provider linked', ['user_id' => $userId, 'provider' => $provider]);

        $result = $this->service->linkProvider(
            $userId,
            $provider,
            $providerUserId,
            $accessToken,
            $refreshToken,
            $expiresAt,
            $scope,
            $metadata
        );

        $this->assertInstanceOf(OAuthProvider::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($provider, $result->getProvider());
        $this->assertSame($providerUserId, $result->getProviderUserId());
    }

    public function testLinkProviderUpdatesExisting(): void
    {
        $userId = 'user_123';
        $provider = 'google';
        $providerUserId = 'google_456';
        $existing = new OAuthProvider(
            $this->generateIdStub(),
            $userId,
            $provider,
            $providerUserId
        );

        $newAccessToken = 'new_access';
        $newRefreshToken = 'new_refresh';
        $newExpiresAt = new \DateTime('+2 hours');
        $newScope = 'profile';
        $newMetadata = ['updated' => true];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with([
                'userId' => $userId,
                'provider' => $provider,
                'providerUserId' => $providerUserId,
            ])
            ->willReturn($existing);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');
        $this->em->expects($this->never())
            ->method('persist');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('OAuth provider link updated', $this->anything());

        $result = $this->service->linkProvider(
            $userId,
            $provider,
            $providerUserId,
            $newAccessToken,
            $newRefreshToken,
            $newExpiresAt,
            $newScope,
            $newMetadata
        );

        $this->assertSame($existing, $result);
        $this->assertSame($newAccessToken, $result->getAccessToken());
        $this->assertSame($newRefreshToken, $result->getRefreshToken());
        $this->assertSame($newExpiresAt, $result->getTokenExpiresAt());
        $this->assertSame($newScope, $result->getScope());
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testGetProvider(): void
    {
        $id = 'oauth_123';
        $oAuth = new OAuthProvider($id, 'user', 'google', 'gp', 'token', null, null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($oAuth);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProvider($id);
        $this->assertSame($oAuth, $result);
    }

    public function testGetUserProvider(): void
    {
        $userId = 'user_123';
        $provider = 'google';
        $oAuth = new OAuthProvider('id', $userId, $provider, 'gp1', 'token', null, null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($oAuth);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUserProvider($userId, $provider);
        $this->assertSame($oAuth, $result);
    }

    public function testGetUserProviders(): void
    {
        $userId = 'user_123';
        $oAuth1 = new OAuthProvider('id1', $userId, 'google', 'gp1', 'token1', null, null);
        $oAuth2 = new OAuthProvider('id2', $userId, 'facebook', 'fb1', 'token2', null, null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn([$oAuth1, $oAuth2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUserProviders($userId);
        $this->assertCount(2, $result);
        $this->assertContains($oAuth1, $result);
        $this->assertContains($oAuth2, $result);
    }

    public function testHasProvider(): void
    {
        $userId = 'user_123';
        $provider = 'google';
        $oAuth = new OAuthProvider('id', $userId, $provider, 'gp1', 'token', null, null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($oAuth);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->hasProvider($userId, $provider);
        $this->assertTrue($result);
    }

    public function testHasProviderFalse(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->hasProvider('user', 'google');
        $this->assertFalse($result);
    }

    public function testUnlinkProviderSuccess(): void
    {
        $userId = 'user_123';
        $provider = 'google';
        $oAuth = new OAuthProvider('id', $userId, $provider, 'gp1', 'token', null, null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($oAuth);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($oAuth);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('OAuth provider unlinked', ['user_id' => $userId, 'provider' => $provider]);

        $result = $this->service->unlinkProvider($userId, $provider);
        $this->assertTrue($result);
    }

    public function testUnlinkProviderNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->unlinkProvider('user', 'google');
        $this->assertFalse($result);
    }

    public function testUpdateAccessTokenSuccess(): void
    {
        $userId = 'user_123';
        $provider = 'google';
        $oAuth = new OAuthProvider('id', $userId, $provider, 'gp1', 'old_token', null, null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($oAuth);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $newAccessToken = 'new_token';
        $newRefreshToken = 'new_refresh';
        $newExpiresAt = new \DateTime('+3 hours');

        $result = $this->service->updateAccessToken($userId, $provider, $newAccessToken, $newRefreshToken, $newExpiresAt);
        $this->assertTrue($result);
        $this->assertSame($newAccessToken, $oAuth->getAccessToken());
        $this->assertSame($newRefreshToken, $oAuth->getRefreshToken());
        $this->assertSame($newExpiresAt, $oAuth->getTokenExpiresAt());
    }

    public function testUpdateAccessTokenNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateAccessToken('user', 'google', 'token', null, null);
        $this->assertFalse($result);
    }

    public function testNeedsTokenRefresh(): void
    {
        $userId = 'user_123';
        $provider = 'google';
        $oAuth = new OAuthProvider('id', $userId, $provider, 'gp1', 'token', null, null);
        // Assuming needsRefresh() checks if expiresAt is near; test both branches
        $oAuth->setTokenExpiresAt((new \DateTime('-1 hour'))); // expired

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($oAuth);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->needsTokenRefresh($userId, $provider);
        $this->assertTrue($result);
    }

    public function testNeedsTokenRefreshFalse(): void
    {
        $userId = 'user_123';
        $provider = 'google';
        $oAuth = new OAuthProvider('id', $userId, $provider, 'gp1', 'token', (new \DateTime('+1 day')), null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($oAuth);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->needsTokenRefresh($userId, $provider);
        $this->assertFalse($result);
    }

    public function testGetProvidersNeedingRefresh(): void
    {
        $userId = 'user_123';
        $needsRefresh = new OAuthProvider('id1', $userId, 'google', 'gp1', 'token', (new \DateTime('-1 hour')), null);
        $fresh = new OAuthProvider('id2', $userId, 'facebook', 'fb1', 'token', (new \DateTime('+1 day')), null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn([$needsRefresh, $fresh]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProvidersNeedingRefresh($userId);
        $this->assertCount(1, $result);
        $this->assertSame($needsRefresh, $result[0]);
    }

    public function testGetProviderNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProvider('nonexistent');
        $this->assertNull($result);
    }

    public function testCountByUser(): void
    {
        $userId = 'user_123';
        $count = 2;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(o.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.userId = :userId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
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
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(OAuthProvider::class)
            ->willReturn($repo);

        $result = $this->service->countByUser($userId);
        $this->assertSame($count, $result);
    }

    /**
     * Generate stub ID.
     */
    private function generateIdStub(): string
    {
        return 'oauth_123';
    }
}
