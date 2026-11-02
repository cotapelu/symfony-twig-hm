<?php

declare(strict_types=1);

namespace Tests\Unit\Audit\EventSubscriber;

use App\Audit\EventSubscriber\AuditSubscriber;
use App\Audit\Service\SimpleAuditLogger;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

class AuditSubscriberTest extends TestCase
{
    private SimpleAuditLogger $logger;
    private AuditSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(SimpleAuditLogger::class);
        $this->subscriber = new AuditSubscriber($this->logger);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertContains('onFlush', $events);
    }

    public function testOnFlushLogsInsertions(): void
    {
        // Mocks
        $entity = new \stdClass();
        $entity->name = 'test';

        $uow = $this->createMock(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')
            ->willReturn([$entity]);

        $uow->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $uow->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')
            ->willReturn($uow);

        $args = $this->createMock(OnFlushEventArgs::class);
        $args->method('getObjectManager')
            ->willReturn($em);

        // Expect logger log for insertion
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('system'),
                $this->equalTo('create'),
                $this->equalTo('stdClass'), // entity name
                $this->equalTo([])
            );

        $this->subscriber->onFlush($args);
    }

    public function testOnFlushLogsUpdates(): void
    {
        $entity = new \stdClass();
        $entity->name = 'test';

        $changeSet = [['old' => 'old', 'new' => 'new']]; // not exact

        $uow = $this->createMock(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->method('getScheduledEntityUpdates')
            ->willReturn([$entity]);
        $uow->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $uow->method('getEntityChangeSet')
            ->with($entity)
            ->willReturn(['field' => ['old', 'new']]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')
            ->willReturn($uow);

        $args = $this->createMock(OnFlushEventArgs::class);
        $args->method('getObjectManager')
            ->willReturn($em);

        // Expect logger log for update
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('system'),
                $this->equalTo('update'),
                $this->equalTo('stdClass'),
                $this->arrayHasKey('field')
            );

        $this->subscriber->onFlush($args);
    }

    public function testOnFlushLogsDeletions(): void
    {
        $entity = new \stdClass();

        $uow = $this->createMock(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $uow->method('getScheduledEntityDeletions')
            ->willReturn([$entity]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')
            ->willReturn($uow);

        $args = $this->createMock(OnFlushEventArgs::class);
        $args->method('getObjectManager')
            ->willReturn($em);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('system'),
                $this->equalTo('delete'),
                $this->equalTo('stdClass'),
                $this->equalTo([])
            );

        $this->subscriber->onFlush($args);
    }
}
