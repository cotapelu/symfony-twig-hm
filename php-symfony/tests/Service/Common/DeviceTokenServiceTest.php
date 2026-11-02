<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\DeviceToken;
use App\Repository\Common\DeviceTokenRepository;
use App\Service\Common\DeviceTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeviceTokenServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private DeviceTokenRepository $repository;
    private LoggerInterface $logger;
    private DeviceTokenService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeviceTokenRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->with(DeviceToken::class)
            ->willReturn($this->repository);
    }

    public function testRegisterTokenWhenExistingUpdatesLastUsed(): void
    {
        $userId = 'user-1';
        $tokenValue = 'existing-token';
        $platform = 'ios';
        $existing = $this->createMock(DeviceToken::class);
        $existing->method('getId')->willReturn('token-id-123');
        // setLastUsedAt should accept DateTime
        $existing->expects($this->once())->method('setLastUsedAt')->with($this->isInstanceOf(\DateTime::class));

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $tokenValue])
            ->willReturn($existing);

        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Device token updated', $this->arrayHasKey('token_id'));

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->registerToken($userId, $tokenValue, $platform);
        $this->assertSame($existing, $result);
    }

    public function testRegisterTokenWhenNotExistsCreatesNew(): void
    {
        $userId = 'user-2';
        $tokenValue = 'new-token';
        $platform = 'android';
        $deviceName = 'Pixel';
        $deviceModel = 'Pixel 8';
        $osVersion = 'Android 14';
        $appVersion = '1.2.3';

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $tokenValue])
            ->willReturn(null);

        // Capture persisted entity
        $persisted = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeviceToken $dt) use (&$persisted, $userId, $tokenValue, $platform, $deviceName, $deviceModel, $osVersion, $appVersion) {
                $persisted = $dt;
                return $persisted !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Device token registered', $this->arrayHasKey('token_id'));

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->registerToken($userId, $tokenValue, $platform, $deviceName, $deviceModel, $osVersion, $appVersion);

        $this->assertInstanceOf(DeviceToken::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($tokenValue, $result->getToken());
        $this->assertSame($platform, $result->getPlatform());
    }

    public function testGetToken(): void
    {
        $id = 'token-id';
        $token = $this->createMock(DeviceToken::class);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($token);

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->getToken($id);
        $this->assertSame($token, $result);
    }

    public function testGetTokenByValue(): void
    {
        $tokenValue = 'abc123';
        $token = $this->createMock(DeviceToken::class);
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $tokenValue])
            ->willReturn($token);

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->getTokenByValue($tokenValue);
        $this->assertSame($token, $result);
    }

    public function testGetUserTokens(): void
    {
        $userId = 'user-3';
        $tokens = [$this->createMock(DeviceToken::class)];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($tokens);

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->getUserTokens($userId);
        $this->assertSame($tokens, $result);
    }

    public function testGetTokensByPlatform(): void
    {
        $platform = 'ios';
        $tokens = [$this->createMock(DeviceToken::class)];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['platform' => $platform, 'status' => 'ACTIVE'])
            ->willReturn($tokens);

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->getTokensByPlatform($platform);
        $this->assertSame($tokens, $result);
    }

    public function testRevokeTokenSuccess(): void
    {
        $tokenId = 'token-id';
        $token = $this->createMock(DeviceToken::class);
        $token->method('getUserId')->willReturn('user-123');
        $token->expects($this->once())->method('setStatus')->with('REVOKED');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($tokenId)
            ->willReturn($token);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Device token revoked', ['token_id' => $tokenId, 'user_id' => 'user-123']);

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->revokeToken($tokenId);
        $this->assertTrue($result);
    }

    public function testRevokeTokenNotFound(): void
    {
        $tokenId = 'missing';
        $this->repository->expects($this->once())
            ->method('find')
            ->with($tokenId)
            ->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->revokeToken($tokenId);
        $this->assertFalse($result);
    }

    public function testRevokeTokenByValueSuccess(): void
    {
        $tokenValue = 'token-value';
        $token = $this->createMock(DeviceToken::class);
        $token->expects($this->once())->method('setStatus')->with('REVOKED');
        $token->method('getUserId')->willReturn('user-123');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $tokenValue])
            ->willReturn($token);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Device token revoked by value', $this->arrayHasKey('token'));

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->revokeTokenByValue($tokenValue);
        $this->assertTrue($result);
    }

    public function testRevokeTokenByValueNotFound(): void
    {
        $tokenValue = 'missing';
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $tokenValue])
            ->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->revokeTokenByValue($tokenValue);
        $this->assertFalse($result);
    }

    public function testRevokeAllUserTokens(): void
    {
        $userId = 'user-4';
        $tokens = [$this->createMock(DeviceToken::class), $this->createMock(DeviceToken::class)];
        foreach ($tokens as $t) {
            $t->expects($this->once())->method('setStatus')->with('REVOKED');
            $t->method('getUserId')->willReturn($userId);
        }

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($tokens);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('All user device tokens revoked', ['user_id' => $userId, 'count' => 2]);

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->revokeAllUserTokens($userId);
        $this->assertSame(2, $result);
    }

    public function testUpdateLastUsedSuccess(): void
    {
        $tokenId = 'token-id';
        $token = $this->createMock(DeviceToken::class);
        $token->method('isActive')->willReturn(true);
        $token->expects($this->once())->method('setLastUsedAt')->with($this->isInstanceOf(\DateTime::class));

        $this->repository->expects($this->once())
            ->method('find')
            ->with($tokenId)
            ->willReturn($token);
        $this->em->expects($this->once())->method('flush');

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->updateLastUsed($tokenId);
        $this->assertTrue($result);
    }

    public function testUpdateLastUsedFailsWhenTokenNotFound(): void
    {
        $tokenId = 'missing';
        $this->repository->expects($this->once())
            ->method('find')
            ->with($tokenId)
            ->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->updateLastUsed($tokenId);
        $this->assertFalse($result);
    }

    public function testUpdateLastUsedFailsWhenTokenInactive(): void
    {
        $tokenId = 'token-id';
        $token = $this->createMock(DeviceToken::class);
        $token->method('isActive')->willReturn(false);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($tokenId)
            ->willReturn($token);
        $this->em->expects($this->never())->method('flush');

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->updateLastUsed($tokenId);
        $this->assertFalse($result);
    }

    public function testCountByPlatform(): void
    {
        $platform = 'ios';
        $this->repository->expects($this->once())
            ->method('count')
            ->with(['platform' => $platform, 'status' => 'ACTIVE'])
            ->willReturn(5);

        $this->service = new DeviceTokenService($this->em, $this->logger);
        $result = $this->service->countByPlatform($platform);
        $this->assertSame(5, $result);
    }
}
