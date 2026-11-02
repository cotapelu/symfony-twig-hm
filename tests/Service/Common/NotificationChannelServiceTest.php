<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\NotificationChannel;
use App\Service\Common\NotificationChannelService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationChannelServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private NotificationChannelService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new NotificationChannelService($this->em, $this->logger);
    }

    public function testRegisterChannelNew(): void
    {
        $userId = 'user_123';
        $channel = NotificationChannelService::CHANNEL_EMAIL;
        $endpoint = 'user@example.com';
        $preferences = ['frequency' => 'daily'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(NotificationChannel::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (NotificationChannel $c) use ($userId, $channel, $endpoint, $preferences) {
                return $c->getUserId() === $userId
                    && $c->getChannel() === $channel
                    && $c->getEndpoint() === $endpoint
                    && $c->getPreferences() === $preferences;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification channel registered', $this->anything());

        $result = $this->service->registerChannel($userId, $channel, $endpoint, $preferences);
        $this->assertInstanceOf(NotificationChannel::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($channel, $result->getChannel());
    }

    public function testRegisterChannelInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->registerChannel('user', 'INVALID');
    }

    public function testRegisterChannelUpdatesExisting(): void
    {
        $userId = 'user_123';
        $channel = NotificationChannelService::CHANNEL_PUSH;
        $existing = new NotificationChannel($this->generateIdStub(), $userId, $channel);
        $existing->setEndpoint('old_endpoint');
        $existing->setStatus('INACTIVE');

        $newEndpoint = 'new_endpoint';
        $newPrefs = ['sound' => 'enabled'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
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
            ->with('Notification channel updated', $this->anything());

        $result = $this->service->registerChannel($userId, $channel, $newEndpoint, $newPrefs);
        $this->assertSame($existing, $result);
        $this->assertSame($newEndpoint, $result->getEndpoint());
        $this->assertSame($newPrefs, $result->getPreferences());
        $this->assertSame('ACTIVE', $result->getStatus());
    }

    public function testGetChannel(): void
    {
        $id = 'channel_123';
        $channel = new NotificationChannel($id, 'user', NotificationChannelService::CHANNEL_SMS);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($channel);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(NotificationChannel::class)
            ->willReturn($repo);

        $result = $this->service->getChannel($id);
        $this->assertSame($channel, $result);
    }

    public function testGetUserChannel(): void
    {
        $userId = 'user_123';
        $channelType = NotificationChannelService::CHANNEL_WEBHOOK;
        $channel = new NotificationChannel('id', $userId, $channelType);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channelType])
            ->willReturn($channel);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUserChannel($userId, $channelType);
        $this->assertSame($channel, $result);
    }

    public function testGetUserChannels(): void
    {
        $userId = 'user_123';
        $channels = [
            new NotificationChannel('id1', $userId, NotificationChannelService::CHANNEL_EMAIL),
            new NotificationChannel('id2', $userId, NotificationChannelService::CHANNEL_PUSH),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($channels);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUserChannels($userId);
        $this->assertCount(2, $result);
    }

    public function testUpdateChannelStatus(): void
    {
        $channelId = 'channel_123';
        $channel = new NotificationChannel($channelId, 'user', NotificationChannelService::CHANNEL_EMAIL);
        $channel->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($channelId)
            ->willReturn($channel);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification channel status updated', $this->anything());

        $result = $this->service->updateChannelStatus($channelId, 'INACTIVE');
        $this->assertSame($channel, $result);
        $this->assertSame('INACTIVE', $result->getStatus());
    }

    public function testSetPreferences(): void
    {
        $userId = 'user_123';
        $channelType = NotificationChannelService::CHANNEL_PUSH;
        $channel = new NotificationChannel('id', $userId, $channelType);
        $newPrefs = ['frequency' => 'weekly', 'quiet_hours' => ['22:00', '06:00']];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channelType])
            ->willReturn($channel);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->setPreferences($userId, $channelType, $newPrefs);
        $this->assertSame($channel, $result);
        $this->assertSame($newPrefs, $result->getPreferences());
    }

    public function testDisableAllForUser(): void
    {
        $userId = 'user_123';
        $channels = [
            new NotificationChannel('id1', $userId, NotificationChannelService::CHANNEL_EMAIL),
            new NotificationChannel('id2', $userId, NotificationChannelService::CHANNEL_SMS),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($channels);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        foreach ($channels as $c) {
            $this->em->expects($this->once())
                ->method('flush');
        }

        $result = $this->service->disableAllForUser($userId);
        $this->assertCount(2, $result);
        foreach ($result as $c) {
            $this->assertSame('INACTIVE', $c->getStatus());
        }
    }

    public function testDeleteChannel(): void
    {
        $channelId = 'channel_123';
        $channel = new NotificationChannel($channelId, 'user', NotificationChannelService::CHANNEL_EMAIL);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($channelId)
            ->willReturn($channel);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($channel);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteChannel($channelId);
        $this->assertTrue($result);
    }

    public function testDeleteChannelNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deleteChannel('nonexistent');
        $this->assertFalse($result);
    }

    public function testCountByUser(): void
    {
        $userId = 'user_123';
        $count = 2;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(c.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('c.userId = :userId')
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
            ->with('c')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(NotificationChannel::class)
            ->willReturn($repo);

        $result = $this->service->countByUser($userId);
        $this->assertSame($count, $result);
    }

    public function testGetActiveChannels(): void
    {
        $channels = [
            new NotificationChannel('id1', 'user', NotificationChannelService::CHANNEL_EMAIL),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($channels);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getActiveChannels();
        $this->assertCount(1, $result);
    }

    public function testIsValidChannel(): void
    {
        $this->assertTrue($this->service->isValidChannel(NotificationChannelService::CHANNEL_EMAIL));
        $this->assertTrue($this->service->isValidChannel(NotificationChannelService::CHANNEL_SMS));
        $this->assertTrue($this->service->isValidChannel(NotificationChannelService::CHANNEL_PUSH));
        $this->assertTrue($this->service->isValidChannel(NotificationChannelService::CHANNEL_WEBHOOK));
        $this->assertFalse($this->service->isValidChannel('unknown'));
    }

    /**
     * Generate stub ID.
     */
    private function generateIdStub(): string
    {
        return 'channel_123';
    }
}
