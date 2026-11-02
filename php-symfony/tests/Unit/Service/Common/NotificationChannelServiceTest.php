<?php declare(strict_types=1);

namespace App\Tests\Unit\Service\Common;

use App\Entity\Common\NotificationChannel;
use App\Service\Common\NotificationChannelService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NotificationChannelServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $emMock;
    private LoggerInterface&MockObject $loggerMock;
    private EntityRepository&MockObject $repoMock;
    private NotificationChannelService $service;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->repoMock = $this->createMock(EntityRepository::class);
        $this->emMock->method('getRepository')
            ->with(NotificationChannel::class)
            ->willReturn($this->repoMock);
        $this->service = new NotificationChannelService($this->emMock, $this->loggerMock);
    }

    private function createNotificationChannelEntity(array $data = []): NotificationChannel
    {
        $id = $data['id'] ?? '123e4567-e89b-12d3-a456-426614174000';
        $userId = $data['userId'] ?? 'user1';
        $channel = $data['channel'] ?? NotificationChannelService::CHANNEL_EMAIL;
        $status = $data['status'] ?? 'ACTIVE';
        $endpoint = $data['endpoint'] ?? null;
        $preferences = $data['preferences'] ?? null;

        $chan = new NotificationChannel($id, $userId, $channel);
        $chan->setEndpoint($endpoint);
        $chan->setPreferences($preferences);
        $chan->setStatus($status);
        return $chan;
    }

    // ==================== getAvailableChannels ====================

    public function testGetAvailableChannels(): void
    {
        $channels = $this->service->getAvailableChannels();
        $this->assertSame([
            NotificationChannelService::CHANNEL_EMAIL,
            NotificationChannelService::CHANNEL_SMS,
            NotificationChannelService::CHANNEL_PUSH,
            NotificationChannelService::CHANNEL_WEBHOOK,
        ], $channels);
    }

    // ==================== isValidChannel ====================

    public function testIsValidChannel_Valid(): void
    {
        $this->assertTrue($this->service->isValidChannel(NotificationChannelService::CHANNEL_EMAIL));
    }

    public function testIsValidChannel_Invalid(): void
    {
        $this->assertFalse($this->service->isValidChannel('INVALID'));
    }

    // ==================== registerChannel ====================

    public function testRegisterChannel_InvalidChannel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->registerChannel('user1', 'BAD');
    }

    public function testRegisterChannel_ExistingChannel_Updates(): void
    {
        $existing = $this->createNotificationChannelEntity(['userId' => 'user1', 'channel' => 'EMAIL', 'endpoint' => null, 'preferences' => null]);
        $this->repoMock->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 'user1', 'channel' => 'EMAIL'])
            ->willReturn($existing);

        $this->emMock->expects($this->once())->method('flush');

        $this->loggerMock->expects($this->once())->method('info')->with('Notification channel updated', $this->arrayHasKey('user_id'));

        $result = $this->service->registerChannel('user1', 'EMAIL', 'new-endpoint', ['key' => 'val']);
        $this->assertSame($existing, $result);
        // verify updates
        $this->assertSame('new-endpoint', $existing->getEndpoint());
        $this->assertSame(['key' => 'val'], $existing->getPreferences());
        $this->assertSame('ACTIVE', $existing->getStatus());
    }

    public function testRegisterChannel_NewChannel_Creates(): void
    {
        $this->repoMock->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 'user1', 'channel' => 'EMAIL'])
            ->willReturn(null);

        $this->emMock->expects($this->once())->method('persist');
        $this->emMock->expects($this->once())->method('flush');

        $this->loggerMock->expects($this->once())->method('info')->with('Notification channel registered', $this->arrayHasKey('user_id'));

        $result = $this->service->registerChannel('user1', 'EMAIL', 'endpoint', ['k' => 'v']);
        $this->assertInstanceOf(NotificationChannel::class, $result);
        $this->assertSame('user1', $result->getUserId());
        $this->assertSame('EMAIL', $result->getChannel());
        $this->assertSame('endpoint', $result->getEndpoint());
        $this->assertSame(['k' => 'v'], $result->getPreferences());
        $this->assertSame('ACTIVE', $result->getStatus());
    }

    // ==================== getChannel ====================

    public function testGetChannel_Found(): void
    {
        $channel = $this->createNotificationChannelEntity(['id' => 'id123']);
        $this->repoMock->expects($this->once())->method('find')->with('id123')->willReturn($channel);
        $result = $this->service->getChannel('id123');
        $this->assertSame($channel, $result);
    }

    public function testGetChannel_NotFound(): void
    {
        $this->repoMock->expects($this->once())->method('find')->with('unknown')->willReturn(null);
        $result = $this->service->getChannel('unknown');
        $this->assertNull($result);
    }

    // ==================== getUserChannel ====================

    public function testGetUserChannel_Found(): void
    {
        $channel = $this->createNotificationChannelEntity(['userId' => 'user1', 'channel' => 'SMS']);
        $this->repoMock->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 'user1', 'channel' => 'SMS'])
            ->willReturn($channel);
        $result = $this->service->getUserChannel('user1', 'SMS');
        $this->assertSame($channel, $result);
    }

    public function testGetUserChannel_NotFound(): void
    {
        $this->repoMock->expects($this->once())->method('findOneBy')->with(['userId' => 'user1', 'channel' => 'EMAIL'])->willReturn(null);
        $result = $this->service->getUserChannel('user1', 'EMAIL');
        $this->assertNull($result);
    }

    // ==================== getUserChannels ====================

    public function testGetUserChannels(): void
    {
        $channels = [
            $this->createNotificationChannelEntity(['userId' => 'user1', 'status' => 'ACTIVE']),
            $this->createNotificationChannelEntity(['userId' => 'user1', 'channel' => 'SMS', 'status' => 'ACTIVE']),
        ];
        $this->repoMock->expects($this->once())
            ->method('findBy')
            ->with(['userId' => 'user1', 'status' => 'ACTIVE'])
            ->willReturn($channels);
        $result = $this->service->getUserChannels('user1');
        $this->assertCount(2, $result);
    }

    // ==================== getActiveChannels ====================

    // This simply calls getUserChannels, so reuse.

    public function testGetActiveChannels(): void
    {
        $channels = [$this->createNotificationChannelEntity()];
        $this->repoMock->method('findBy')->willReturn($channels);
        $result = $this->service->getActiveChannels('user1');
        $this->assertCount(1, $result);
    }

    // ==================== hasChannel ====================

    public function testHasChannel_True(): void
    {
        $channel = $this->createNotificationChannelEntity();
        $this->repoMock->method('findOneBy')->willReturn($channel);
        $result = $this->service->hasChannel('user1', 'EMAIL');
        $this->assertTrue($result);
    }

    public function testHasChannel_False(): void
    {
        $this->repoMock->method('findOneBy')->willReturn(null);
        $result = $this->service->hasChannel('user1', 'EMAIL');
        $this->assertFalse($result);
    }

    // ==================== hasAnyChannel ====================

    public function testHasAnyChannel_True(): void
    {
        $this->repoMock->method('findBy')->willReturn([$this->createNotificationChannelEntity()]);
        $result = $this->service->hasAnyChannel('user1');
        $this->assertTrue($result);
    }

    public function testHasAnyChannel_False(): void
    {
        $this->repoMock->method('findBy')->willReturn([]);
        $result = $this->service->hasAnyChannel('user1');
        $this->assertFalse($result);
    }

    // ==================== disableChannel ====================

    public function testDisableChannel_Success(): void
    {
        $channel = $this->createNotificationChannelEntity(['status' => 'ACTIVE']);
        $this->repoMock->method('findOneBy')->willReturn($channel);
        $this->emMock->expects($this->once())->method('flush');
        $this->loggerMock->expects($this->once())->method('info')->with('Notification channel disabled', $this->arrayHasKey('user_id'));
        $result = $this->service->disableChannel('user1', 'EMAIL');
        $this->assertTrue($result);
        $this->assertSame('DISABLED', $channel->getStatus());
    }

    public function testDisableChannel_NotFound(): void
    {
        $this->repoMock->method('findOneBy')->willReturn(null);
        $result = $this->service->disableChannel('user1', 'EMAIL');
        $this->assertFalse($result);
    }

    // ==================== enableChannel ====================

    public function testEnableChannel_Success(): void
    {
        $channel = $this->createNotificationChannelEntity(['status' => 'DISABLED']);
        $this->repoMock->method('findOneBy')->willReturn($channel);
        $this->emMock->expects($this->once())->method('flush');
        $result = $this->service->enableChannel('user1', 'EMAIL');
        $this->assertTrue($result);
        $this->assertSame('ACTIVE', $channel->getStatus());
    }

    public function testEnableChannel_NotFound(): void
    {
        $this->repoMock->method('findOneBy')->willReturn(null);
        $result = $this->service->enableChannel('user1', 'EMAIL');
        $this->assertFalse($result);
    }

    // ==================== updateEndpoint ====================

    public function testUpdateEndpoint_Success(): void
    {
        $channel = $this->createNotificationChannelEntity(['endpoint' => null]);
        $this->repoMock->method('findOneBy')->willReturn($channel);
        $this->emMock->expects($this->once())->method('flush');
        $result = $this->service->updateEndpoint('user1', 'EMAIL', 'new-endpoint');
        $this->assertTrue($result);
        $this->assertSame('new-endpoint', $channel->getEndpoint());
    }

    public function testUpdateEndpoint_NotFound(): void
    {
        $this->repoMock->method('findOneBy')->willReturn(null);
        $result = $this->service->updateEndpoint('user1', 'EMAIL', 'endpoint');
        $this->assertFalse($result);
    }

    // ==================== updatePreferences ====================

    public function testUpdatePreferences_Success(): void
    {
        $channel = $this->createNotificationChannelEntity(['preferences' => null]);
        $this->repoMock->method('findOneBy')->willReturn($channel);
        $this->emMock->expects($this->once())->method('flush');
        $prefs = ['key' => 'value'];
        $result = $this->service->updatePreferences('user1', 'EMAIL', $prefs);
        $this->assertTrue($result);
        $this->assertSame($prefs, $channel->getPreferences());
    }

    public function testUpdatePreferences_NotFound(): void
    {
        $this->repoMock->method('findOneBy')->willReturn(null);
        $result = $this->service->updatePreferences('user1', 'EMAIL', ['k'=>'v']);
        $this->assertFalse($result);
    }

    // ==================== deleteChannel ====================

    public function testDeleteChannel_Success(): void
    {
        $channel = $this->createNotificationChannelEntity();
        $this->repoMock->method('findOneBy')->willReturn($channel);
        $this->emMock->expects($this->once())->method('remove')->with($channel);
        $this->emMock->expects($this->once())->method('flush');
        $this->loggerMock->expects($this->once())->method('info')->with('Notification channel deleted', $this->arrayHasKey('user_id'));
        $result = $this->service->deleteChannel('user1', 'EMAIL');
        $this->assertTrue($result);
    }

    public function testDeleteChannel_NotFound(): void
    {
        $this->repoMock->method('findOneBy')->willReturn(null);
        $result = $this->service->deleteChannel('user1', 'EMAIL');
        $this->assertFalse($result);
    }

    // ==================== getUsersByChannel ====================

    public function testGetUsersByChannel(): void
    {
        $users = [$this->createNotificationChannelEntity()];
        $this->repoMock->expects($this->once())
            ->method('findBy')
            ->with(['channel' => 'EMAIL', 'status' => 'ACTIVE'])
            ->willReturn($users);
        $result = $this->service->getUsersByChannel('EMAIL');
        $this->assertCount(1, $result);
    }

    // ==================== generateId format ====================

    public function testGenerateId_Format(): void
    {
        $method = new \ReflectionMethod($this->service, 'generateId');
        $method->setAccessible(true);
        $id = $method->invoke($this->service);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id);
    }
}
