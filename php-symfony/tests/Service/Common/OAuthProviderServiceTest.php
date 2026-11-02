<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\OAuthProvider;
use App\Repository\Common\OAuthProviderRepository;
use App\Service\Common\OAuthProviderService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class OAuthProviderServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private OAuthProviderRepository $providerRepo;
    private LoggerInterface $logger;
    private OAuthProviderService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->providerRepo = $this->createMock(OAuthProviderRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->willReturnMap([
                [OAuthProvider::class, $this->providerRepo],
            ]);
    }

    public function testLinkProviderCreatesNew(): void
    {
        $userId = 'user-1';
        $provider = 'google';
        $providerUserId = 'google-123';
        $accessToken = 'token';
        $refreshToken = 'refresh';
        $expires = new DateTime('+1 hour');
        $scope = 'profile email';
        $metadata = ['picture' => 'url'];

        $created = null;
        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->with([
                'userId' => $userId,
                'provider' => $provider,
                'providerUserId' => $providerUserId,
            ])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (OAuthProvider $p) use (&$created, $userId, $provider, $providerUserId, $accessToken, $refreshToken, $expires, $scope, $metadata) {
                $created = $p;
                return $created !== null
                    && $created->getUserId() === $userId
                    && $created->getProvider() === $provider
                    && $created->getProviderUserId() === $providerUserId
                    && $created->getAccessToken() === $accessToken
                    && $created->getRefreshToken() === $refreshToken
                    && $created->getTokenExpiresAt() === $expires
                    && $created->getScope() === $scope
                    && $created->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('OAuth provider linked', $this->arrayHasKey('user_id'));

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->linkProvider(
            $userId,
            $provider,
            $providerUserId,
            $accessToken,
            $refreshToken,
            $expires,
            $scope,
            $metadata
        );

        $this->assertSame($created, $result);
    }

    public function testLinkProviderWithNoOptionalFields(): void
    {
        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())->method('info');

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->linkProvider('u', 'p', 'pid');
        $this->assertInstanceOf(OAuthProvider::class, $result);
    }

    public function testLinkProviderUpdatesExisting(): void
    {
        $userId = 'user-2';
        $provider = 'github';
        $providerUserId = 'gh-456';
        $newToken = 'new-token';
        $newRefresh = 'new-refresh';
        $newExpires = new DateTime('+1 day');
        $newScope = 'repo';
        $newMetadata = ['login' => 'user'];

        $existing = $this->createMock(OAuthProvider::class);
        $existing->expects($this->once())->method('setAccessToken')->with($newToken)->willReturnSelf();
        $existing->expects($this->once())->method('setRefreshToken')->with($newRefresh)->willReturnSelf();
        $existing->expects($this->once())->method('setTokenExpiresAt')->with($newExpires)->willReturnSelf();
        $existing->expects($this->once())->method('setScope')->with($newScope)->willReturnSelf();
        $existing->expects($this->once())->method('setMetadata')->with($newMetadata)->willReturnSelf();

        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->with([
                'userId' => $userId,
                'provider' => $provider,
                'providerUserId' => $providerUserId,
            ])
            ->willReturn($existing);

        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('OAuth provider link updated', $this->arrayHasKey('user_id'));

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->linkProvider(
            $userId,
            $provider,
            $providerUserId,
            $newToken,
            $newRefresh,
            $newExpires,
            $newScope,
            $newMetadata
        );

        $this->assertSame($existing, $result);
    }

    public function testGetProvider(): void
    {
        $id = 'oauth-1';
        $provider = $this->createMock(OAuthProvider::class);
        $this->providerRepo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($provider);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->getProvider($id);
        $this->assertSame($provider, $result);
    }

    public function testGetUserProvider(): void
    {
        $userId = 'user-3';
        $provider = 'facebook';
        $providerRecord = $this->createMock(OAuthProvider::class);
        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($providerRecord);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->getUserProvider($userId, $provider);
        $this->assertSame($providerRecord, $result);
    }

    public function testGetUserProviders(): void
    {
        $userId = 'user-4';
        $providers = [$this->createMock(OAuthProvider::class), $this->createMock(OAuthProvider::class)];
        $this->providerRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($providers);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->getUserProviders($userId);
        $this->assertSame($providers, $result);
    }

    public function testHasProviderTrue(): void
    {
        $providerRecord = $this->createMock(OAuthProvider::class);
        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($providerRecord);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->hasProvider('user-5', 'twitter');
        $this->assertTrue($result);
    }

    public function testHasProviderFalse(): void
    {
        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->hasProvider('user-6', 'linkedin');
        $this->assertFalse($result);
    }

    public function testUnlinkProviderSuccess(): void
    {
        $userId = 'user-7';
        $provider = 'microsoft';
        $providerRecord = $this->createMock(OAuthProvider::class);

        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($providerRecord);
        $this->em->expects($this->once())->method('remove')->with($providerRecord);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('OAuth provider unlinked', $this->arrayHasKey('user_id'));

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->unlinkProvider($userId, $provider);
        $this->assertTrue($result);
    }

    public function testUnlinkProviderNotFound(): void
    {
        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $this->em->expects($this->never())->method('remove');

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->unlinkProvider('user-8', 'apple');
        $this->assertFalse($result);
    }

    public function testUpdateAccessTokenSuccess(): void
    {
        $userId = 'user-9';
        $provider = 'github';
        $accessToken = 'new-access';
        $refreshToken = 'new-refresh';
        $expires = new DateTime('+2 hours');

        $providerRecord = $this->createMock(OAuthProvider::class);
        $providerRecord->expects($this->once())->method('setAccessToken')->with($accessToken)->willReturnSelf();
        $providerRecord->expects($this->once())->method('setRefreshToken')->with($refreshToken)->willReturnSelf();
        $providerRecord->expects($this->once())->method('setTokenExpiresAt')->with($expires)->willReturnSelf();

        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'provider' => $provider])
            ->willReturn($providerRecord);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('OAuth access token updated', $this->arrayHasKey('user_id'));

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->updateAccessToken($userId, $provider, $accessToken, $refreshToken, $expires);
        $this->assertTrue($result);
    }

    public function testUpdateAccessTokenNotFound(): void
    {
        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->updateAccessToken('user-10', 'provider', 'token');
        $this->assertFalse($result);
    }

    public function testNeedsTokenRefreshTrue(): void
    {
        $providerRecord = $this->createMock(OAuthProvider::class);
        $providerRecord->expects($this->once())
            ->method('needsRefresh')
            ->willReturn(true);

        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($providerRecord);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->needsTokenRefresh('user-11', 'provider');
        $this->assertTrue($result);
    }

    public function testNeedsTokenRefreshFalse(): void
    {
        $providerRecord = $this->createMock(OAuthProvider::class);
        $providerRecord->expects($this->once())
            ->method('needsRefresh')
            ->willReturn(false);

        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($providerRecord);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->needsTokenRefresh('user-12', 'provider');
        $this->assertFalse($result);
    }

    public function testNeedsTokenRefreshWhenNoProvider(): void
    {
        $this->providerRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->needsTokenRefresh('user-13', 'provider');
        $this->assertFalse($result);
    }

    public function testGetProvidersNeedingRefresh(): void
    {
        $userId = 'user-14';
        $allProviders = [$this->createMock(OAuthProvider::class), $this->createMock(OAuthProvider::class), $this->createMock(OAuthProvider::class)];
        $allProviders[0]->expects($this->once())->method('needsRefresh')->willReturn(true);
        $allProviders[1]->expects($this->once())->method('needsRefresh')->willReturn(false);
        $allProviders[2]->expects($this->once())->method('needsRefresh')->willReturn(true);

        $this->providerRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($allProviders);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->getProvidersNeedingRefresh($userId);
        $this->assertCount(2, $result);
        $this->assertSame($allProviders[0], $result[0]);
        $this->assertSame($allProviders[2], $result[1]);
    }

    public function testGetProvidersNeedingRefreshEmpty(): void
    {
        $this->providerRepo->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $this->service = new OAuthProviderService($this->em, $this->logger);
        $result = $this->service->getProvidersNeedingRefresh('user-15');
        $this->assertSame([], $result);
    }
}
