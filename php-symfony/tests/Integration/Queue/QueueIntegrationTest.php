<?php

declare(strict_types=1);

namespace App\Tests\Integration\Queue;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Queue\Application\Service\QueueService;
use App\Queue\Domain\Repository\QueueRepositoryInterface;
use App\Queue\Domain\Repository\QueueTicketRepositoryInterface;
use App\Queue\Domain\Entity\Queue;
use App\Queue\Domain\Entity\QueueTicket;
use App\Government\Domain\Entity\Officer;
use App\Government\Domain\ValueObject\Position;
use App\Government\Domain\ValueObject\Department;
use App\Government\Domain\ValueObject\OfficerId;

#[AllowMockObjectsWithoutExpectations]
class QueueIntegrationTest extends DatabaseIntegrationTestCase
{
    private QueueService $queueService;
    private QueueRepositoryInterface $queueRepo;
    private QueueTicketRepositoryInterface $ticketRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $em = self::getEntityManager();
        $this->queueRepo = $em->getRepository(Queue::class);
        $this->ticketRepo = $em->getRepository(QueueTicket::class);
        $this->queueService = new QueueService($this->queueRepo, $this->ticketRepo);
    }

    public function testCreateQueueAndPersist(): void
    {
        $queue = $this->queueService->createQueue(
            name: 'Test Queue',
            wardCode: 'W001',
            serviceTypes: ['Cấp CCCD', 'Hộ khẩu'],
            departmentName: 'Phường 1',
            description: 'Queue for testing',
            counterCount: 2,
            operatingHours: ['mon' => '08:00-12:00', 'tue' => '08:00-12:00']
        );

        $this->assertNotNull($queue->getId());

        // Verify persisted
        $found = $this->queueRepo->findById($queue->getId());
        $this->assertNotNull($found);
        $this->assertSame('Test Queue', $found->getName());
        $this->assertSame('W001', $found->getWardCode());
        $this->assertSame(['Cấp CCCD', 'Hộ khẩu'], $found->getServiceTypes());
    }

    public function testActivateAndDeactivateQueue(): void
    {
        $queue = $this->queueService->createQueue(
            name: 'Active Test',
            wardCode: 'W002',
            serviceTypes: ['Đăng ký khai sinh']
        );

        // Initially active (default)
        $saved = $this->queueRepo->findById($queue->getId());
        $this->assertTrue($saved->isActive());

        // Deactivate
        $deactivated = $this->queueService->deactivateQueue($queue->getId());
        $this->assertFalse($deactivated->isActive());

        // Activate
        $activated = $this->queueService->activateQueue($queue->getId());
        $this->assertTrue($activated->isActive());
    }

    public function testJoinQueueCreatesTicket(): void
    {
        // Set operating hours to always open (current day)
        $now = new \DateTimeImmutable();
        $dayOfWeek = (int)$now->format('N'); // 1 (Mon) to 7 (Sun)
        $operatingHours = [$dayOfWeek => ['open' => '00:00', 'close' => '23:59']];

        $queue = $this->queueService->createQueue(
            name: 'Join Test Queue',
            wardCode: 'W003',
            serviceTypes: ['General Enquiry'],
            operatingHours: $operatingHours
        );
        $this->queueService->activateQueue($queue->getId());

        $ticket = $this->queueService->joinQueue(
            queueId: $queue->getId(),
            serviceType: 'General Enquiry',
            citizenName: 'Nguyễn Văn Test',
            citizenPhone: '0900123456'
        );

        $this->assertNotNull($ticket->getId());
        $this->assertSame('waiting', $ticket->getStatus());
        $this->assertStringStartsWith('GENERAL ENQUIRY-', $ticket->getTicketNumber());

        $found = $this->ticketRepo->findById($ticket->getId());
        $this->assertNotNull($found);
        $this->assertSame($queue->getId(), $found->getQueue()->getId());
    }

    public function testGetQueueStatistics(): void
    {
        // Set operating hours to always open (current day)
        $now = new \DateTimeImmutable();
        $dayOfWeek = (int)$now->format('N');
        $operatingHours = [$dayOfWeek => ['open' => '00:00', 'close' => '23:59']];

        $queue = $this->queueService->createQueue(
            name: 'Stats Queue',
            wardCode: 'W004',
            serviceTypes: ['Khác'],
            operatingHours: $operatingHours
        );
        $this->queueService->activateQueue($queue->getId());

        // Join a few tickets
        $this->queueService->joinQueue($queue->getId(), 'Khác', 'Citizen 1', '0900111111');
        $this->queueService->joinQueue($queue->getId(), 'Khác', 'Citizen 2', '0900222222');

        $stats = $this->queueService->getQueueStatistics($queue->getId());
        $this->assertIsArray($stats);
        $this->assertSame($queue->getId(), $stats['queueId']);
        $this->assertSame(2, $stats['waitingCount']);
        $this->assertSame(0, $stats['inProgressCount']);
        $this->assertSame(0, $stats['todayCompleted']);
    }

    // Helper to create an Officer entity for testing
    private function createTestOfficer(\Doctrine\ORM\EntityManagerInterface $em): Officer
    {
        $position = new Position(
            code: 'OFF',
            name: 'Test Officer Position',
            level: 1
        );
        $department = new Department(
            code: 'DEPT001',
            name: 'Test Department'
        );
        $officer = new Officer(
            new OfficerId('officer-' . bin2hex(random_bytes(8))),
            'Test Officer',
            $position,
            $department
        );
        $em->persist($officer);
        $em->flush();
        return $officer;
    }

    private function createAlwaysOpenHours(): array
    {
        $now = new \DateTimeImmutable();
        $dayOfWeek = (int)$now->format('N');
        return [$dayOfWeek => ['open' => '00:00', 'close' => '23:59']];
    }

    public function testCancelWaitingTicket(): void
    {
        $queue = $this->queueService->createQueue(
            name: 'Cancel Test Queue',
            wardCode: 'W-CANCEL',
            serviceTypes: ['Test Service'],
            operatingHours: $this->createAlwaysOpenHours()
        );
        $this->queueService->activateQueue($queue->getId());

        $ticket = $this->queueService->joinQueue(
            queueId: $queue->getId(),
            serviceType: 'Test Service',
            citizenName: 'Cancel Citizen',
            citizenPhone: '0900000000'
        );

        $this->assertNotNull($ticket);
        $this->assertSame(QueueTicket::STATUS_WAITING, $ticket->getStatus());

        $cancelled = $this->queueService->cancelTicket($ticket->getId(), reason: 'No longer needed');
        $this->assertNotNull($cancelled);
        $this->assertSame(QueueTicket::STATUS_CANCELLED, $cancelled->getStatus());
    }

    public function testGetTicketByNumber(): void
    {
        $queue = $this->queueService->createQueue(
            name: 'Lookup Queue',
            wardCode: 'W-LOOKUP',
            serviceTypes: ['Lookup Service'],
            operatingHours: $this->createAlwaysOpenHours()
        );
        $this->queueService->activateQueue($queue->getId());

        $ticket = $this->queueService->joinQueue(
            queueId: $queue->getId(),
            serviceType: 'Lookup Service',
            citizenName: 'Lookup Citizen',
            citizenPhone: '0911111111'
        );

        $this->assertNotNull($ticket);
        $found = $this->queueService->getTicketByNumber($ticket->getTicketNumber());
        $this->assertNotNull($found);
        $this->assertSame($ticket->getId(), $found->getId());
    }

    public function testGetTicketsByPhone(): void
    {
        $queue = $this->queueService->createQueue(
            name: 'Phone Queue',
            wardCode: 'W-PHONE',
            serviceTypes: ['Phone Service'],
            operatingHours: $this->createAlwaysOpenHours()
        );
        $this->queueService->activateQueue($queue->getId());

        $phone = '0922222222';
        $ticket = $this->queueService->joinQueue(
            queueId: $queue->getId(),
            serviceType: 'Phone Service',
            citizenName: 'Phone Citizen',
            citizenPhone: $phone
        );

        $this->assertNotNull($ticket);
        $tickets = $this->queueService->getTicketsByPhone($phone);
        $this->assertCount(1, $tickets);
        $this->assertSame($ticket->getId(), $tickets[0]->getId());
    }

    public function testCallNextTicketAndStart(): void
    {
        $queue = $this->queueService->createQueue(
            name: 'Call Start Queue',
            wardCode: 'W-CALL',
            serviceTypes: ['Call Service'],
            operatingHours: $this->createAlwaysOpenHours()
        );
        $this->queueService->activateQueue($queue->getId());

        $ticket = $this->queueService->joinQueue(
            queueId: $queue->getId(),
            serviceType: 'Call Service',
            citizenName: 'Call Citizen',
            citizenPhone: '0933333333'
        );

        $this->assertSame(QueueTicket::STATUS_WAITING, $ticket->getStatus());

        $em = self::$entityManager;
        $officer = $this->createTestOfficer($em);

        // Call next ticket
        $called = $this->queueService->callNextTicket(
            $queue->getId(),
            $officer,
            'counter-1'
        );
        $this->assertNotNull($called);
        $this->assertSame(QueueTicket::STATUS_CALLED, $called->getStatus());
        $this->assertSame('counter-1', $called->getCounterNumber());
        $this->assertNotNull($called->getCalledAt());

        // Start the ticket
        $started = $this->queueService->startTicket($called->getId());
        $this->assertNotNull($started);
        $this->assertSame(QueueTicket::STATUS_IN_PROGRESS, $started->getStatus());
        $this->assertNotNull($started->getStartedAt());
    }

    public function testSkipTicket(): void
    {
        $queue = $this->queueService->createQueue(
            name: 'Skip Queue',
            wardCode: 'W-SKIP',
            serviceTypes: ['Skip Service'],
            operatingHours: $this->createAlwaysOpenHours()
        );
        $this->queueService->activateQueue($queue->getId());

        $ticket = $this->queueService->joinQueue(
            queueId: $queue->getId(),
            serviceType: 'Skip Service',
            citizenName: 'Skip Citizen',
            citizenPhone: '0944444444'
        );

        $em = self::$entityManager;
        $officer = $this->createTestOfficer($em);

        // Call the ticket first
        $called = $this->queueService->callNextTicket($queue->getId(), $officer, 'counter-2');
        $this->assertSame(QueueTicket::STATUS_CALLED, $called->getStatus());

        // Skip
        $skipped = $this->queueService->skipTicket($called->getId());
        $this->assertNotNull($skipped);
        $this->assertSame(QueueTicket::STATUS_SKIPPED, $skipped->getStatus());
        // skip clears calledAt and counter, but we can assert it's not called anymore
        $this->assertNull($skipped->getCalledAt());
        $this->assertNull($skipped->getCounterNumber());
        $this->assertNull($skipped->getServedByOfficer());
    }
}
