<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\NotificationChannel;
use App\Service\Common\NotificationChannelService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationChannelServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private NotificationChannelService $service;

    // We'll use a generic repository mock
    private $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);

        // Set up getRepository to return $this->repo when NotificationChannel class is requested
        $this->em->method('getRepository')
            ->with(NotificationChannel::class)
            ->willReturn($this->repo);

        $this->service = new NotificationChannelService($this->em, $this->logger);
    }

    public function testRegisterChannelThrowsForInvalidChannel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid notification channel: INVALID');
        $this->service->registerChannel('user-1', 'INVALID');
    }

    public function testRegisterChannelWhenExistingUpdatesEndpointAndPreferences(): void
    {
        $userId = 'user-1';
        $channel = NotificationChannelService::CHANNEL_EMAIL;
        $endpoint = 'new@endpoint';
        $preferences = ['locale' => 'vi'];

        $existing = $this->createMock(NotificationChannel::class);
        $existing->method('getId')->willReturn('channel-id');

        // Expect findOneBy to return existing
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn($existing);

        // Expect endpoint and preferences set, and status set, then flush
        $existing->expects($this->once())->method('setEndpoint')->with($endpoint);
        $existing->expects($this->once())->method('setPreferences')->with($preferences);
        $existing->expects($this->once())->method('setStatus')->with('ACTIVE');
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification channel updated', $this->arrayHasKey('user_id'));

        $result = $this->service->registerChannel($userId, $channel, $endpoint, $preferences);
        $this->assertSame($existing, $result);
    }

    public function testRegisterChannelWhenNotExistsCreatesNew(): void
    {
        $userId = 'user-2';
        $channel = NotificationChannelService::CHANNEL_SMS;
        $endpoint = 'sms://123456';
        $preferences = null;

        // No existing
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn(null);

        // Capture persisted entity
        $persisted = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (NotificationChannel $nc) use (&$persisted, $userId, $channel, $endpoint) {
                $persisted = $nc;
                $this->assertSame($userId, $nc->getUserId());
                $this->assertSame($channel, $nc->getChannel());
                $this->assertSame($endpoint, $nc->getEndpoint());
                return $persisted !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification channel registered', $this->arrayHasKey('user_id'));

        $result = $this->service->registerChannel($userId, $channel, $endpoint, $preferences);
        $this->assertInstanceOf(NotificationChannel::class, $result);
        $this->assertSame($persisted, $result);
    }

    public function testGetChannelCallsFind(): void
    {
        $channelId = 'chan-id';
        $expected = $this->createMock(NotificationChannel::class);
        $this->repo->expects($this->once())
            ->method('find')
            ->with($channelId)
            ->willReturn($expected);

        $result = $this->service->getChannel($channelId);
        $this->assertSame($expected, $result);
    }

    public function testGetUserChannelCallsFindOneBy(): void
    {
        $userId = 'user-1';
        $channel = NotificationChannelService::CHANNEL_PUSH;
        $expected = $this->createMock(NotificationChannel::class);
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn($expected);

        $result = $this->service->getUserChannel($userId, $channel);
        $this->assertSame($expected, $result);
    }

    public function testGetUserChannelsCallsFindByWithStatusActive(): void
    {
        $userId = 'user-1';
        $expected = [$this->createMock(NotificationChannel::class)];
        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->service->getUserChannels($userId);
        $this->assertSame($expected, $result);
    }

    public function testGetActiveChannelsDelegatesToGetUserChannels(): void
    {
        $userId = 'user-1';
        $expected = [$this->createMock(NotificationChannel::class)];
        // We'll spy on getUserChannels
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserChannels');
        $method->setAccessible(true);
        // Actually getActiveChannels just calls getUserChannels. We'll just assert that.
        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->service->getActiveChannels($userId);
        $this->assertSame($expected, $result);
    }

    public function testHasChannelReturnsTrueWhenChannelExists(): void
    {
        $userId = 'user-1';
        $channel = NotificationChannelService::CHANNEL_WEBHOOK;
        $existing = $this->createMock(NotificationChannel::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn($existing);

        $result = $this->service->hasChannel($userId, $channel);
        $this->assertTrue($result);
    }

    public function testHasChannelReturnsFalseWhenNotFound(): void
    {
        $userId = 'user-2';
        $channel = NotificationChannelService::CHANNEL_EMAIL;
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn(null);

        $result = $this->service->hasChannel($userId, $channel);
        $this->assertFalse($result);
    }

    public function testHasAnyChannelReturnsTrueWhenChannelsExist(): void
    {
        $userId = 'user-1';
        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn([$this->createMock(NotificationChannel::class)]);

        $result = $this->service->hasAnyChannel($userId);
        $this->assertTrue($result);
    }

    public function testHasAnyChannelReturnsFalseWhenEmpty(): void
    {
        $userId = 'user-3';
        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId, 'status' => 'ACTIVE'])
            ->willReturn([]);

        $result = $this->service->hasAnyChannel($userId);
        $this->assertFalse($result);
    }

    public function testDisableChannelWhenFoundSetsStatusDisabledAndFlushes(): void
    {
        $userId = 'user-1';
        $channel = NotificationChannelService::CHANNEL_SMS;
        $channelEntity = $this->createMock(NotificationChannel::class);
        $channelEntity->expects($this->once())->method('setStatus')->with('DISABLED');
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn($channelEntity);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification channel disabled', $this->arrayHasKey('user_id'));

        $result = $this->service->disableChannel($userId, $channel);
        $this->assertTrue($result);
    }

    public function testDisableChannelWhenNotFoundReturnsFalse(): void
    {
        $userId = 'user-2';
        $channel = NotificationChannelService::CHANNEL_EMAIL;
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn(null);
        // No flush expected

        $result = $this->service->disableChannel($userId, $channel);
        $this->assertFalse($result);
    }

    public function testEnableChannelWhenFoundSetsStatusActiveAndFlushes(): void
    {
        $userId = 'user-1';
        $channel = NotificationChannelService::CHANNEL_PUSH;
        $channelEntity = $this->createMock(NotificationChannel::class);
        $channelEntity->expects($this->once())->method('setStatus')->with('ACTIVE');
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn($channelEntity);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification channel enabled', $this->arrayHasKey('user_id'));

        $result = $this->service->enableChannel($userId, $channel);
        $this->assertTrue($result);
    }

    public function testEnableChannelWhenNotFoundReturnsFalse(): void
    {
        $userId = 'user-2';
        $channel = NotificationChannelService::CHANNEL_WEBHOOK;
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn(null);

        $result = $this->service->enableChannel($userId, $channel);
        $this->assertFalse($result);
    }

    public function testUpdateEndpointWhenFoundSavesAndFlushes(): void
    {
        $userId = 'user-1';
        $channel = NotificationChannelService::CHANNEL_EMAIL;
        $newEndpoint = 'mailto:new@example.com';
        $channelEntity = $this->createMock(NotificationChannel::class);
        $channelEntity->expects($this->once())->method('setEndpoint')->with($newEndpoint);
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn($channelEntity);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification channel endpoint updated', $this->arrayHasKey('user_id'));

        $result = $this->service->updateEndpoint($userId, $channel, $newEndpoint);
        $this->assertTrue($result);
    }

    public function testUpdateEndpointWhenNotFoundReturnsFalse(): void
    {
        $userId = 'user-2';
        $channel = NotificationChannelService::CHANNEL_SMS;
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn(null);

        $result = $this->service->updateEndpoint($userId, $channel, 'new');
        $this->assertFalse($result);
    }

    public function testUpdatePreferencesWhenFoundSavesAndFlushes(): void
    {
        $userId = 'user-1';
        $channel = NotificationChannelService::CHANNEL_PUSH;
        $prefs = ['lang' => 'en'];
        $channelEntity = $this->createMock(NotificationChannel::class);
        $channelEntity->expects($this->once())->method('setPreferences')->with($prefs);
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn($channelEntity);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification channel preferences updated', $this->arrayHasKey('user_id'));

        $result = $this->service->updatePreferences($userId, $channel, $prefs);
        $this->assertTrue($result);
    }

    public function testUpdatePreferencesWhenNotFoundReturnsFalse(): void
    {
        $userId = 'user-2';
        $channel = NotificationChannelService::CHANNEL_WEBHOOK;
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn(null);

        $result = $this->service->updatePreferences($userId, $channel, []);
        $this->assertFalse($result);
    }

    public function testDeleteChannelWhenFoundRemovesAndFlushes(): void
    {
        $userId = 'user-1';
        $channel = NotificationChannelService::CHANNEL_SMS;
        $channelEntity = $this->createMock(NotificationChannel::class);
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn($channelEntity);
        $this->em->expects($this->once())->method('remove')->with($channelEntity);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification channel deleted', $this->arrayHasKey('user_id'));

        $result = $this->service->deleteChannel($userId, $channel);
        $this->assertTrue($result);
    }

    public function testDeleteChannelWhenNotFoundReturnsFalse(): void
    {
        $userId = 'user-2';
        $channel = NotificationChannelService::CHANNEL_EMAIL;
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'channel' => $channel])
            ->willReturn(null);

        $result = $this->service->deleteChannel($userId, $channel);
        $this->assertFalse($result);
    }

    public function testGetUsersByChannelCallsFindByWithChannelAndActive(): void
    {
        $channel = NotificationChannelService::CHANNEL_PUSH;
        $expected = [$this->createMock(NotificationChannel::class)];
        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['channel' => $channel, 'status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->service->getUsersByChannel($channel);
        $this->assertSame($expected, $result);
    }

    public function testGetAvailableChannelsReturnsConstantArray(): void
    {
        $result = $this->service->getAvailableChannels();
        $this->assertContains(NotificationChannelService::CHANNEL_EMAIL, $result);
        $this->assertContains(NotificationChannelService::CHANNEL_SMS, $result);
        $this->assertContains(NotificationChannelService::CHANNEL_PUSH, $result);
        $this->assertContains(NotificationChannelService::CHANNEL_WEBHOOK, $result);
        $this->assertCount(4, $result);
    }

    public function testIsValidChannelAcceptsValid(): void
    {
        $this->assertTrue($this->service->isValidChannel(NotificationChannelService::CHANNEL_EMAIL));
        $this->assertTrue($this->service->isValidChannel(NotificationChannelService::CHANNEL_SMS));
        $this->assertTrue($this->service->isValidChannel(NotificationChannelService::CHANNEL_PUSH));
        $this->assertTrue($this->service->isValidChannel(NotificationChannelService::CHANNEL_WEBHOOK));
    }

    public function testIsValidChannelRejectsInvalid(): void
    {
        $this->assertFalse($this->service->isValidChannel('INVALID'));
    }
}
