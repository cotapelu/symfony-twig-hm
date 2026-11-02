<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notification\Entity\NotificationChannel;
use App\Core\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationChannelTest extends TestCase
{
    private function createMockUser(string $id = 'user-123'): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    public function testConstructor(): void
    {
        $user = $this->createMockUser();
        $channel = new NotificationChannel($user, NotificationChannel::CHANNEL_PUSH);

        $this->assertIsString($channel->getId());
        $this->assertSame($user, $channel->getUser());
        $this->assertSame(NotificationChannel::CHANNEL_PUSH, $channel->getChannel());
        $this->assertNull($channel->getEndpoint());
        $this->assertNull($channel->getPreferences());
        $this->assertSame(NotificationChannel::STATUS_ACTIVE, $channel->getStatus());
        $this->assertInstanceOf(\DateTime::class, $channel->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $channel->getUpdatedAt());
        $this->assertTrue($channel->isActive());
    }

    public function testSetters(): void
    {
        $user = $this->createMockUser();
        $channel = new NotificationChannel($user, NotificationChannel::CHANNEL_EMAIL);
        $channel->setEndpoint('https://fcm.com/token');
        $this->assertSame('https://fcm.com/token', $channel->getEndpoint());

        $channel->setPreferences(['sound' => 'default']);
        $this->assertSame(['sound' => 'default'], $channel->getPreferences());

        $channel->setStatus(NotificationChannel::STATUS_INACTIVE);
        $this->assertFalse($channel->isActive());

        $channel->setChannel(NotificationChannel::CHANNEL_SMS);
        $this->assertSame(NotificationChannel::CHANNEL_SMS, $channel->getChannel());
    }

    public function testToArray(): void
    {
        $user = $this->createMockUser('user-abc');
        $channel = new NotificationChannel($user, NotificationChannel::CHANNEL_PUSH);
        $channel->setEndpoint('endpoint-url');
        $channel->setPreferences(['key' => 'value']);

        // set timestamps via reflection to fix for toArray output
        $created = new \DateTime('2025-01-01 00:00:00');
        $updated = new \DateTime('2025-01-02 00:00:00');
        $ref1 = new \ReflectionProperty($channel, 'createdAt');
        $ref1->setAccessible(true);
        $ref1->setValue($channel, $created);
        $ref2 = new \ReflectionProperty($channel, 'updatedAt');
        $ref2->setAccessible(true);
        $ref2->setValue($channel, $updated);

        $arr = $channel->toArray();

        $this->assertSame([
            'id' => $channel->getId(),
            'userId' => 'user-abc',
            'channel' => NotificationChannel::CHANNEL_PUSH,
            'endpoint' => 'endpoint-url',
            'preferences' => ['key' => 'value'],
            'status' => NotificationChannel::STATUS_ACTIVE,
            'createdAt' => '2025-01-01 00:00:00',
            'updatedAt' => '2025-01-02 00:00:00',
        ], $arr);
    }
}
