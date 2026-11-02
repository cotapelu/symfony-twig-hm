<?php

declare(strict_types=1);

namespace App\Audit\EventSubscriber;

use App\Audit\Service\SimpleAuditLogger;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use ReflectionClass;

class AuditSubscriber implements EventSubscriber
{
    public function __construct(private SimpleAuditLogger $logger) {}

    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $entityName = function (object $entity): string {
            return (new ReflectionClass($entity))->getShortName();
        };

        // Insertions
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->logger->log('system', 'create', $entityName($entity), []);
        }

        // Updates
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $changes = $uow->getEntityChangeSet($entity);
            $normalized = [];
            foreach ($changes as $field => $values) {
                [$old, $new] = $values;
                if ($old instanceof \DateTimeInterface) $old = $old->format('c');
                if ($new instanceof \DateTimeInterface) $new = $new->format('c');
                $normalized[$field] = ['old' => $old, 'new' => $new];
            }
            $this->logger->log('system', 'update', $entityName($entity), $normalized);
        }

        // Deletions
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->logger->log('system', 'delete', $entityName($entity), []);
        }
    }
}
