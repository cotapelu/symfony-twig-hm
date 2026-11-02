<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notification\Entity\Notification;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationTest extends TestCase
{
    private function createNotification(array $overrides = []): Notification
    {
        $data = array_merge([
            'userId' => '1',
            'type' => 'info',
            'title' => 'Test',
            'message' => 'Message',
            'channel' => 'email',
        ], $overrides);
        return new Notification($data['userId'], $data['type'], $data['title'], $data['message'], $data['channel']);
    }

    public function testConstructor(): void
    {
        $notif = $this->createNotification();
        $this->assertNull($notif->getId());
        $this->assertSame('1', $notif->getUserId());
        $this->assertSame('info', $notif->getType());
        $this->assertSame('Test', $notif->getTitle());
        $this->assertSame('Message', $notif->getMessage());
        $this->assertNull($notif->getData());
        $this->assertFalse($notif->isRead());
        $this->assertFalse($notif->isSent());
        $this->assertSame('email', $notif->getChannel());
        $this->assertInstanceOf(\DateTimeImmutable::class, $notif->getCreatedAt());
        $this->assertNull($notif->getSentAt());
        $this->assertNull($notif->getReadAt());
    }

    public function testGettersAndSetters(): void
    {
        $notif = $this->createNotification();
        $notif->setId(100);
        $this->assertSame(100, $notif->getId());

        $notif->setUserId('2');
        $this->assertSame('2', $notif->getUserId());

        $notif->setType('warning');
        $this->assertSame('warning', $notif->getType());

        $notif->setTitle('New Title');
        $this->assertSame('New Title', $notif->getTitle());

        $notif->setMessage('New Message');
        $this->assertSame('New Message', $notif->getMessage());

        $notif->setData(['key' => 'value']);
        $this->assertSame(['key' => 'value'], $notif->getData());

        $notif->setChannel('push');
        $this->assertSame('push', $notif->getChannel());

        $now = new \DateTimeImmutable();
        $notif->setCreatedAt($now);
        $this->assertSame($now, $notif->getCreatedAt());

        $sent = new \DateTimeImmutable('-1 hour');
        $notif->setSentAt($sent);
        $this->assertSame($sent, $notif->getSentAt());

        $read = new \DateTimeImmutable('-30 minutes');
        $notif->setReadAt($read);
        $this->assertSame($read, $notif->getReadAt());
    }

    public function testMarkAsRead(): void
    {
        $notif = $this->createNotification();
        $this->assertFalse($notif->isRead());
        $this->assertNull($notif->getReadAt());
        $notif->markAsRead();
        $this->assertTrue($notif->isRead());
        $this->assertInstanceOf(\DateTimeImmutable::class, $notif->getReadAt());
    }

    public function testMarkAsSent(): void
    {
        $notif = $this->createNotification();
        $this->assertFalse($notif->isSent());
        $this->assertNull($notif->getSentAt());
        $notif->markAsSent();
        $this->assertTrue($notif->isSent());
        $this->assertInstanceOf(\DateTimeImmutable::class, $notif->getSentAt());
    }
}
