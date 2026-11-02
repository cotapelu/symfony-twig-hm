<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\EventStore;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing event store (event sourcing)
 */
class EventStoreService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * Append an event to the event store
     */
    public function appendEvent(
        string $eventType,
        ?array $payload = null,
        ?string $aggregateId = null,
        ?string $aggregateType = null,
        ?int $version = null,
        ?array $metadata = null
    ) {
        $event = $this->buildEvent($eventType, $payload, $aggregateId, $aggregateType, $version, $metadata);
        $this->em->persist($event);
        $this->em->flush();
        $this->logger->debug('Event appended', [
            'event_id' => $event->getId(),
            'event_type' => $eventType,
            'aggregate_id' => $aggregateId,
        ]);
        return $event;
    }

    /**
     * Get event by ID
     */
    public function getEvent(string $eventId)
    {
        return $this->em->getRepository(EventStore::class)->find($eventId);
    }

    /**
     * Get all events for an aggregate
     */
    public function getEventsForAggregate(string $aggregateId, ?string $aggregateType = null)
    {
        $qb = $this->em->getRepository(EventStore::class)
            ->createQueryBuilder('e');

        $qb->where('e.aggregateId = :aggregateId')
            ->setParameter('aggregateId', $aggregateId);

        if ($aggregateType !== null) {
            $qb->andWhere('e.aggregateType = :aggregateType')
                ->setParameter('aggregateType', $aggregateType);
        }

        $qb->orderBy('e.createdAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get events by date range
     */
    public function getEventsByDateRange(\DateTime $start, \DateTime $end)
    {
        return $this->em->getRepository(EventStore::class)
            ->createQueryBuilder('e')
            ->where('e.createdAt >= :start')
            ->andWhere('e.createdAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete old events before a cutoff date
     */
    public function deleteOldEvents(\DateTime $cutoff)
    {
        $deleted = $this->em->getRepository(EventStore::class)
            ->createQueryBuilder('e')
            ->delete()
            ->where('e.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();

        $this->logger->info('Old events deleted', [
            'cutoff' => $cutoff->format(\DateTime::ATOM),
            'deleted_count' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Get event count by type
     */
    public function countByType(string $eventType)
    {
        return $this->em->getRepository(EventStore::class)
            ->count(['eventType' => $eventType]);
    }

    /**
     * Get latest event for an aggregate
     */
    public function getLatestEvent(string $aggregateId, ?string $aggregateType = null)
    {
        $qb = $this->em->getRepository(EventStore::class)
            ->createQueryBuilder('e');

        $qb->where('e.aggregateId = :aggregateId')
            ->setParameter('aggregateId', $aggregateId);

        if ($aggregateType !== null) {
            $qb->andWhere('e.aggregateType = :aggregateType')
                ->setParameter('aggregateType', $aggregateType);
        }

        $qb->orderBy('e.version', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Generate unique ID
     */
    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    private function buildEvent(
        string $eventType,
        ?array $payload,
        ?string $aggregateId,
        ?string $aggregateType,
        ?int $version,
        ?array $metadata
    ): EventStore {
        $event = new EventStore(
            $this->generateId(),
            $eventType
        );
        $event->setPayload($payload);
        $event->setAggregateId($aggregateId);
        $event->setAggregateType($aggregateType);
        $event->setVersion($version);
        $event->setMetadata($metadata);
        return $event;
    }
}
