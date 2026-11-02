<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Entity;

use App\Notification\Entity\Notification;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    public function testConstructorSetsRequiredProperties(): void
    {
        $n = new Notification(123, 'order', 'Order Update', 'Your order has shipped', 'push');
        $this->assertNull($n->getId());
        $this->assertEquals(123, $n->getUserId());
        $this->assertEquals('order', $n->getType());
        $this->assertEquals('Order Update', $n->getTitle());
        $this->assertEquals('Your order has shipped', $n->getMessage());
        $this->assertEquals('push', $n->getChannel());
        $this->assertFalse($n->isRead());
        $this->assertFalse($n->isSent());
    }

    public function testSetData(): void
    {
        $n = new Notification(123, 'order', 'Test', 'Test', 'push');
        $n->setData(['order_id' => 456, 'amount' => 100]);
        $this->assertEquals(['order_id' => 456, 'amount' => 100], $n->getData());
    }

    public function testMarkAsRead(): void
    {
        $n = new Notification(123, 'order', 'Test', 'Test', 'push');
        $this->assertFalse($n->isRead());
        $this->assertNull($n->getReadAt());
        $n->markAsRead();
        $this->assertTrue($n->isRead());
        $this->assertInstanceOf(\DateTime::class, $n->getReadAt());
    }

    public function testMarkAsSent(): void
    {
        $n = new Notification(123, 'order', 'Test', 'Test', 'push');
        $this->assertFalse($n->isSent());
        $this->assertNull($n->getSentAt());
        $n->markAsSent();
        $this->assertTrue($n->isSent());
        $this->assertInstanceOf(\DateTime::class, $n->getSentAt());
    }

    public function testSetId(): void
    {
        $n = new Notification(123, 'order', 'Test', 'Test', 'push');
        $n->setId(456);
        $this->assertEquals(456, $n->getId());
    }
}
