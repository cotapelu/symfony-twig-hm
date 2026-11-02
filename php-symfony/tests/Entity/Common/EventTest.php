<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\Event;
use DateTime;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EventTest extends TestCase
{
    private function createEvent(
        string $id = 'event-id',
        string $entityType = 'order',
        string $entityId = 'order-1',
        string $eventType = 'order.created'
    ): Event {
        return new Event($id, $entityType, $entityId, $eventType);
    }

    public function testConstructor(): void
    {
        $e = $this->createEvent();
        $this->assertSame('event-id', $e->getId());
        $this->assertSame('order', $e->getEntityType());
        $this->assertSame('order-1', $e->getEntityId());
        $this->assertSame('order.created', $e->getEventType());
        $this->assertNull($e->getData());
        $this->assertNull($e->getUserId());
        $this->assertInstanceOf(DateTime::class, $e->getCreatedAt());
    }

    public function testSetData(): void
    {
        $e = $this->createEvent();
        $data = ['key' => 'value'];
        $e->setData($data);
        $this->assertSame($data, $e->getData());
        $e->setData(null);
        $this->assertNull($e->getData());
    }

    public function testSetUserId(): void
    {
        $e = $this->createEvent();
        $e->setUserId('user-123');
        $this->assertSame('user-123', $e->getUserId());
        $e->setUserId(null);
        $this->assertNull($e->getUserId());
    }
}
