<?php

declare(strict_types=1);

namespace App\Tests\Integration\Utility;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Utility\Entity\Event;

#[AllowMockObjectsWithoutExpectations]
class EventIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrieveEvent(): void
    {
        $event = new Event('user.created');
        $event->setEntityType('User')
            ->setEntityId('user-123')
            ->setData(['email' => 'test@example.com']);

        $em = self::getEntityManager();
        $em->persist($event);
        $em->flush();

        $this->assertNotNull($event->getId());

        $found = $em->getRepository(Event::class)->find($event->getId());
        $this->assertNotNull($found);
        $this->assertSame('user.created', $found->getEventType());
        $this->assertSame('User', $found->getEntityType());
        $this->assertSame('user-123', $found->getEntityId());
        $this->assertSame(['email' => 'test@example.com'], $found->getData());
    }

    public function testUpdateEvent(): void
    {
        $event = new Event('order.placed');
        $event->setEntityType('Order')
            ->setEntityId('order-456')
            ->setData(['total' => 99.99]);

        $em = self::getEntityManager();
        $em->persist($event);
        $em->flush();

        $event->setData(['total' => 149.99, 'currency' => 'USD']);
        $em->flush();

        $found = $em->getRepository(Event::class)->find($event->getId());
        $this->assertSame(['total' => 149.99, 'currency' => 'USD'], $found->getData());
    }

    public function testDeleteEvent(): void
    {
        $event = new Event('payment.received');
        $event->setEntityType('Payment')
            ->setEntityId('pay-789');

        $em = self::getEntityManager();
        $em->persist($event);
        $em->flush();

        $id = $event->getId();
        $em->remove($event);
        $em->flush();

        $found = $em->getRepository(Event::class)->find($id);
        $this->assertNull($found);
    }

    public function testEventIdIsUuid(): void
    {
        $event = new Event('system.startup');
        $em = self::getEntityManager();
        $em->persist($event);
        $em->flush();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $event->getId());
    }
}
