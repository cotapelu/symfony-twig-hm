<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\Notification;
use DateTime;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationTest extends TestCase
{
    private function createNotification(
        string $id = 'notif-id',
        string $userId = 'user-1',
        string $type = 'info',
        string $title = 'Title',
        string $body = 'Body'
    ): Notification {
        return new Notification($id, $userId, $type, $title, $body);
    }

    public function testConstructor(): void
    {
        $n = $this->createNotification();
        $this->assertSame('notif-id', $n->getId());
        $this->assertSame('user-1', $n->getUserId());
        $this->assertSame('info', $n->getType());
        $this->assertSame('Title', $n->getTitle());
        $this->assertSame('Body', $n->getBody());
        $this->assertNull($n->getData());
        $this->assertNull($n->getReadAt());
        $this->assertInstanceOf(DateTime::class, $n->getCreatedAt());
    }

    public function testSetData(): void
    {
        $n = $this->createNotification();
        $data = ['key' => 'value'];
        $n->setData($data);
        $this->assertSame($data, $n->getData());
        $n->setData(null);
        $this->assertNull($n->getData());
    }

    public function testSetReadAt(): void
    {
        $n = $this->createNotification();
        $this->assertNull($n->getReadAt());
        $readAt = new DateTime();
        $n->setReadAt($readAt);
        $this->assertSame($readAt, $n->getReadAt());
        $n->setReadAt(null);
        $this->assertNull($n->getReadAt());
    }
}
