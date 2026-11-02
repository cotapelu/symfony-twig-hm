<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\Event;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;

/**
 * Service for managing domain events
 */
class EventService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * Generate unique UUIDv4
     */
    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x%04x%04x',
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

    /**
     * Validate event data
     */
    private function validateEvent(string $entityType, string $entityId, string $eventType)
    {
        if (empty(trim($entityType))) {
            throw new \InvalidArgumentException('Entity type is required');
        }
        if (empty(trim($entityId))) {
            throw new \InvalidArgumentException('Entity ID is required');
        }
        if (empty(trim($eventType))) {
            throw new \InvalidArgumentException('Event type is required');
        }
    }

    /**
     * Record a new event
     */
    public function record(
        string $entityType,
        string $entityId,
        string $eventType,
        ?array $data = null,
        ?string $userId = null
    ) {
        $this->validateEvent($entityType, $entityId, $eventType);
        $event = $this->buildEventEntity($entityType, $entityId, $eventType, $data, $userId);
        try {
            $this->em->persist($event);
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to record event', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Unable to record event', 0, $e);
        }
        return $event;
    }

    /**
     * Find event by ID
     */
    public function getEvent(string $eventId)
    {
        return $this->em->getRepository(Event::class)->find($eventId);
    }

    /**
     * Get events for an entity
     */
    public function getEventsForEntity(string $entityType, string $entityId)
    {
        return $this->em->getRepository(Event::class)
            ->findBy(['entityType' => $entityType, 'entityId' => $entityId], ['createdAt' => 'ASC']);
    }

    /**
     * Get events by user
     */
    public function getEventsByUser(string $userId)
    {
        return $this->em->getRepository(Event::class)
            ->findBy(['userId' => $userId], ['createdAt' => 'DESC']);
    }

    /**
     * Build event entity (helper)
     */
    private function buildEventEntity(
        string $entityType,
        string $entityId,
        string $eventType,
        ?array $data,
        ?string $userId
    ): Event {
        $event = new Event(
            $this->generateId(),
            $entityType,
            $entityId,
            $eventType
        );
        $event->setData($data);
        $event->setUserId($userId);
        return $event;
    }
}
