<?php

declare(strict_types=1);

namespace App\Shared\DomainEvent;

use App\Shared\Entity\StoredDomainEvent;
use Doctrine\ORM\EntityManagerInterface;

class DomainEventStore
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function save(DomainEvent $event): void
    {
        // Store event for audit trail and event sourcing
        $eventData = $event->toArray();

        // Also log to file for debugging
        error_log(sprintf(
            '[EVENT_STORE] %s: %s',
            $event->eventType(),
            json_encode($eventData)
        ));

        // Store in database
        $storedEvent = new StoredDomainEvent(
            id: uniqid('evt_', true),
            eventType: $event->eventType(),
            aggregateId: $eventData['aggregateId'] ?? 'unknown',
            aggregateType: $eventData['aggregateType'] ?? 'unknown',
            payload: $eventData,
            version: 1,
        );

        $this->entityManager->persist($storedEvent);
        $this->entityManager->flush();
    }

    /**
     * @return StoredDomainEvent[]
     */
    public function getEventsForAggregate(string $aggregateId, string $aggregateType): array
    {
        return $this->entityManager->getRepository(StoredDomainEvent::class)
            ->findBy(
                ['aggregateId' => $aggregateId, 'aggregateType' => $aggregateType],
                ['occurredAt' => 'ASC']
            );
    }

    /**
     * @return StoredDomainEvent[]
     */
    public function getAllEventsSince(string $sinceEventId): array
    {
        $sinceEvent = $this->entityManager->getRepository(StoredDomainEvent::class)
            ->find($sinceEventId);

        if (!$sinceEvent) {
            return [];
        }

        return $this->entityManager->getRepository(StoredDomainEvent::class)
            ->createQueryBuilder('e')
            ->where('e.occurredAt > :since')
            ->setParameter('since', $sinceEvent->getOccurredAt())
            ->orderBy('e.occurredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}