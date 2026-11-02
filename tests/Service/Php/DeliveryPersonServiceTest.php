<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\DeliveryPerson;
use App\Repository\Php\DeliveryPersonRepository;
use App\Service\Php\DeliveryPersonService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeliveryPersonServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DeliveryPersonRepository&MockObject $repository;
    private LoggerInterface&MockObject $logger;
    private DeliveryPersonService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryPersonRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new DeliveryPersonService($this->em, $this->repository);
    }

    public function testCreateDeliveryPersonSuccess(): void
    {
        $userId = 'user_123';
        $type = 'rider';
        $vehicleInfo = ['bike' => 'Honda'];
        $metadata = ['license' => 'ABC123'];

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryPerson $p) use ($userId, $type, $vehicleInfo, $metadata) {
                return $p->getUserId() === $userId
                    && $p->getType() === $type
                    && $p->getVehicleInfo() === $vehicleInfo
                    && $p->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createDeliveryPerson($userId, $type, $vehicleInfo, $metadata);
        $this->assertInstanceOf(DeliveryPerson::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($type, $result->getType());
        $this->assertSame($vehicleInfo, $result->getVehicleInfo());
    }

    public function testGetDeliveryPersonSuccess(): void
    {
        $id = 'person_123';
        $person = new DeliveryPerson($id, 'user', 'rider');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($person);

        $result = $this->service->getDeliveryPerson($id);
        $this->assertSame($person, $result);
    }

    public function testGetDeliveryPersonByUserId(): void
    {
        $userId = 'user_123';
        $person = new DeliveryPerson('id', $userId, 'rider');

        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($person);

        $result = $this->service->getDeliveryPersonByUserId($userId);
        $this->assertSame($person, $result);
    }

    public function testGetAvailableDeliveryPersonsWithType(): void
    {
        $type = 'rider';
        $person = new DeliveryPerson('id', 'user', $type);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ONLINE', 'isAvailable' => true, 'type' => $type])
            ->willReturn([$person]);

        $result = $this->service->getAvailableDeliveryPersons($type);
        $this->assertCount(1, $result);
    }

    public function testGetAvailableDeliveryPersonsWithoutType(): void
    {
        $person = new DeliveryPerson('id', 'user', 'rider');

        $this->repository->expects($this->once())
            ->method('findAvailable')
            ->willReturn([$person]);

        $result = $this->service->getAvailableDeliveryPersons();
        $this->assertCount(1, $result);
    }

    public function testSetOnline(): void
    {
        $id = 'person_123';
        $person = new DeliveryPerson($id, 'user', 'rider');
        $person->setStatus('OFFLINE');
        $person->setIsAvailable(false);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($person);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->setOnline($id);
        $this->assertSame($person, $result);
        $this->assertSame('ONLINE', $result->getStatus());
        $this->assertTrue($result->getIsAvailable());
    }

    public function testSetOnlineNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $result = $this->service->setOnline('nonexistent');
        $this->assertNull($result);
    }

    public function testSetOffline(): void
    {
        $id = 'person_123';
        $person = new DeliveryPerson($id, 'user', 'rider');
        $person->setStatus('ONLINE');
        $person->setIsAvailable(true);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($person);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->setOffline($id);
        $this->assertSame($person, $result);
        $this->assertSame('OFFLINE', $result->getStatus());
        $this->assertFalse($result->getIsAvailable());
    }

    public function testSetOfflineNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $result = $this->service->setOffline('nonexistent');
        $this->assertNull($result);
    }

    public function testAssignOrder(): void
    {
        $personId = 'person_123';
        $orderId = 'order_456';
        $person = new DeliveryPerson($personId, 'user', 'rider');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($personId)
            ->willReturn($person);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->assignOrder($personId, $orderId);
        $this->assertSame($person, $result);
        $this->assertSame($orderId, $result->getCurrentOrderId());
    }

    public function testAssignOrderNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $result = $this->service->assignOrder('nonexistent', 'order_123');
        $this->assertNull($result);
    }

    public function testUnassignOrder(): void
    {
        $personId = 'person_123';
        $orderId = 'order_456';
        $person = new DeliveryPerson($personId, 'user', 'rider');
        $person->setCurrentOrderId($orderId);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($personId)
            ->willReturn($person);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->unassignOrder($personId);
        $this->assertSame($person, $result);
        $this->assertNull($result->getCurrentOrderId());
    }

    public function testUnassignOrderNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $result = $this->service->unassignOrder('nonexistent');
        $this->assertNull($result);
    }

    public function testCompleteOrder(): void
    {
        $personId = 'person_123';
        $orderId = 'order_456';
        $person = new DeliveryPerson($personId, 'user', 'rider');
        $person->setCurrentOrderId($orderId);
        $person->setTotalDelivered(10);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($personId)
            ->willReturn($person);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->completeOrder($personId, $orderId);
        $this->assertSame($person, $result);
        $this->assertNull($result->getCurrentOrderId());
        $this->assertSame(11, $result->getTotalDelivered());
    }

    public function testCompleteOrderInvalidAssignment(): void
    {
        $personId = 'person_123';
        $orderId = 'order_456';
        $person = new DeliveryPerson($personId, 'user', 'rider');
        $person->setCurrentOrderId('different_order');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($personId)
            ->willReturn($person);

        $result = $this->service->completeOrder($personId, $orderId);
        $this->assertNull($result);
    }
}
