<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\EventStore;
use DateTime;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EventStoreTest extends TestCase
{
    private function createEventStore(
        string $id = 'event-id',
        string $eventType = 'user.created'
    ): EventStore {
        return new EventStore($id, $eventType);
    }

    public function testConstructor(): void
    {
        $e = $this->createEventStore();
        $this->assertSame('event-id', $e->getId());
        $this->assertSame('user.created', $e->getEventType());
        $this->assertNull($e->getPayload());
        $this->assertNull($e->getAggregateId());
        $this->assertNull($e->getAggregateType());
        $this->assertNull($e->getVersion());
        $this->assertNull($e->getMetadata());
        $this->assertInstanceOf(DateTime::class, $e->getCreatedAt());
    }

    public function testSetPayload(): void
    {
        $e = $this->createEventStore();
        $payload = ['data' => 'value'];
        $e->setPayload($payload);
        $this->assertSame($payload, $e->getPayload());
        $e->setPayload(null);
        $this->assertNull($e->getPayload());
    }

    public function testSetAggregateId(): void
    {
        $e = $this->createEventStore();
        $e->setAggregateId('agg-123');
        $this->assertSame('agg-123', $e->getAggregateId());
        $e->setAggregateId(null);
        $this->assertNull($e->getAggregateId());
    }

    public function testSetAggregateType(): void
    {
        $e = $this->createEventStore();
        $e->setAggregateType('order');
        $this->assertSame('order', $e->getAggregateType());
        $e->setAggregateType(null);
        $this->assertNull($e->getAggregateType());
    }

    public function testSetVersion(): void
    {
        $e = $this->createEventStore();
        $e->setVersion(3);
        $this->assertSame(3, $e->getVersion());
        $e->setVersion(null);
        $this->assertNull($e->getVersion());
    }

    public function testSetMetadata(): void
    {
        $e = $this->createEventStore();
        $meta = ['source' => 'api'];
        $e->setMetadata($meta);
        $this->assertSame($meta, $e->getMetadata());
        $e->setMetadata(null);
        $this->assertNull($e->getMetadata());
    }
}
