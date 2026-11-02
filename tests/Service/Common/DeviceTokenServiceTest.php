<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\DeviceToken;
use App\Service\Common\DeviceTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeviceTokenServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private DeviceTokenService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new DeviceTokenService($this->em, $this->logger);
    }

    public function testRegisterTokenNew(): void
    {
        $userId = 'user_123';
        $token = 'device_token_abc';
        $platform = 'ios';
        $deviceName = 'iPhone';
        $deviceModel = 'iPhone 12';
        $osVersion = '15.0';
        $appVersion = '1.0.0';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeviceToken::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeviceToken $dt) use (
                $userId, $token, $platform, $deviceName, $deviceModel, $osVersion, $appVersion
            ) {
                return $dt->getUserId() === $userId
                    && $dt->getToken() === $token
                    && $dt->getPlatform() === $platform
                    && $dt->getDeviceName() === $deviceName
                    && $dt->getDeviceModel() === $deviceModel
                    && $dt->getOsVersion() === $osVersion
                    && $dt->getAppVersion() === $appVersion;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Device token registered', $this->anything());

        $result = $this->service->registerToken(
            $userId, $token, $platform, $deviceName, $deviceModel, $osVersion, $appVersion
        );
        $this->assertInstanceOf(DeviceToken::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($token, $result->getToken());
        $this->assertSame($platform, $result->getPlatform());
    }

    public function testRegisterTokenExisting(): void
    {
        $userId = 'user_123';
        $token = 'existing_token';
        $platform = 'android';
        $existing = new DeviceToken($this->generateIdStub(), $userId, $token, $platform);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn($existing);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeviceToken::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');
        $this->em->expects($this->never())
            ->method('persist');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Device token updated', $this->anything());

        $result = $this->service->registerToken($userId, $token, $platform);
        $this->assertSame($existing, $result);
    }

    public function testGetToken(): void
    {
        $id = 'token_123';
        $deviceToken = new DeviceToken($id, 'user', 'token', 'ios');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($deviceToken);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeviceToken::class)
            ->willReturn($repo);

        $result = $this->service->getToken($id);
        $this->assertSame($deviceToken, $result);
    }

    public function testGetTokensByUser(): void
    {
        $userId = 'user_123';
        $tokens = [
            new DeviceToken('id1', $userId, 'token1', 'ios'),
            new DeviceToken('id2', $userId, 'token2', 'android'),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($tokens);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getTokensByUser($userId);
        $this->assertSame($tokens, $result);
    }

    public function testGetTokensByPlatform(): void
    {
        $platform = 'ios';
        $tokens = [
            new DeviceToken('id1', 'user1', 'token1', $platform),
            new DeviceToken('id2', 'user2', 'token2', $platform),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['platform' => $platform])
            ->willReturn($tokens);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getTokensByPlatform($platform);
        $this->assertSame($tokens, $result);
    }

    public function testDeactivateToken(): void
    {
        $tokenId = 'token_123';
        $token = new DeviceToken($tokenId, 'user', 'token_val', 'ios');
        $token->setIsActive(true);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($tokenId)
            ->willReturn($token);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deactivateToken($tokenId);
        $this->assertSame($token, $result);
        $this->assertFalse($result->getIsActive());
    }

    public function testDeactivateTokenNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deactivateToken('nonexistent');
        $this->assertNull($result);
    }

    public function testDeleteToken(): void
    {
        $tokenId = 'token_123';
        $token = new DeviceToken($tokenId, 'user', 'token', 'ios');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($tokenId)
            ->willReturn($token);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($token);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteToken($tokenId);
        $this->assertTrue($result);
    }

    public function testDeleteTokenNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deleteToken('nonexistent');
        $this->assertFalse($result);
    }

    public function testCleanupInactiveTokens(): void
    {
        $threshold = new \DateTime('-30 days');
        $inactive1 = new DeviceToken('id1', 'user1', 'token1', 'ios');
        $inactive2 = new DeviceToken('id2', 'user2', 'token2', 'android');

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('dt.lastUsedAt < :threshold')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('threshold', $threshold)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$inactive1, $inactive2]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('dt')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeviceToken::class)
            ->willReturn($repo);
        foreach ([$inactive1, $inactive2] as $token) {
            $this->em->expects($this->once())
                ->method('remove')
                ->with($token);
        }
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->cleanupInactiveTokens($threshold);
        $this->assertSame(2, $result);
    }

    public function testCountByUser(): void
    {
        $userId = 'user_123';
        $count = 3;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(dt.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('dt.userId = :userId')
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
            ->with('dt')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeviceToken::class)
            ->willReturn($repo);

        $result = $this->service->countByUser($userId);
        $this->assertSame($count, $result);
    }

    /**
     * Generate a stub ID.
     */
    private function generateIdStub(): string
    {
        return 'device_123';
    }
}
