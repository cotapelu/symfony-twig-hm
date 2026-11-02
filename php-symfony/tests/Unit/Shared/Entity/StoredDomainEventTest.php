<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Entity\StoredDomainEvent;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StoredDomainEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $payload = ['key' => 'value', 'num' => 123];
        $event = new StoredDomainEvent(
            id: 'evt-123',
            eventType: 'OrderCreated',
            aggregateId: 'order-1',
            aggregateType: 'Order',
            payload: $payload,
            version: 1
        );

        $this->assertSame('evt-123', $event->getId());
        $this->assertSame('OrderCreated', $event->getEventType());
        $this->assertSame('order-1', $event->getAggregateId());
        $this->assertSame('Order', $event->getAggregateType());
        $this->assertSame($payload, $event->getPayload());
        $this->assertSame(1, $event->getVersion());
        $this->assertInstanceOf(\DateTime::class, $event->getOccurredAt());
    }
}
