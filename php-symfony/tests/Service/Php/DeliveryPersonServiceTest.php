<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryPerson;
use App\Repository\Php\DeliveryPersonRepository;
use App\Service\Php\DeliveryPersonService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryPersonServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DeliveryPersonRepository&MockObject $repository;
    private DeliveryPersonService $service;

    private string $personId;
    private DeliveryPerson $person;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryPersonRepository::class);
        $this->service = new DeliveryPersonService($this->em, $this->repository);

        $this->personId = bin2hex(random_bytes(16));
        $this->person = new DeliveryPerson(
            $this->personId,
            'user-1',
            'John Doe',
            '0123456789'
        );
    }

    public function testCreateDeliveryPersonSuccess(): void
    {
        $userId = 'user-123';
        $name = 'Alice';
        $phone = '0987654321';
        $vehicleId = 'vehicle-1';
        $currentLocation = ['lat' => 10.0, 'lng' => 20.0];
        $metadata = ['rating' => 5];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryPerson $p) use (&$captured, $userId, $name, $phone, $vehicleId, $currentLocation, $metadata) {
                $captured = $p;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createDeliveryPerson($userId, $name, $phone, $vehicleId, $currentLocation, $metadata);
        $this->assertInstanceOf(DeliveryPerson::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($name, $result->getName());
        $this->assertSame($phone, $result->getPhone());
        $this->assertSame($vehicleId, $result->getVehicleId());
        $this->assertSame($currentLocation, $result->getCurrentLocation());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateDeliveryPersonValidationEmptyUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required');
        $this->service->createDeliveryPerson('', 'Name', 'phone');
    }

    public function testCreateDeliveryPersonValidationEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        $this->service->createDeliveryPerson('user', '   ', 'phone');
    }

    public function testCreateDeliveryPersonValidationEmptyPhone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone is required');
        $this->service->createDeliveryPerson('user', 'Name', '');
    }

    public function testGetDeliveryPersonSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->personId)
            ->willReturn($this->person);

        $result = $this->service->getDeliveryPerson($this->personId);
        $this->assertSame($this->person, $result);
    }

    public function testGetDeliveryPersonNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->personId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DeliveryPerson not found');
        $this->service->getDeliveryPerson($this->personId);
    }

    public function testUpdateDeliveryPersonSuccess(): void
    {
        $newName = 'Bob';
        $newPhone = '1112223333';
        $newVehicleId = 'vehicle-2';
        $newLocation = ['lat' => 0, 'lng' => 0];
        $newMetadata = ['status' => 'busy'];
        $newStatus = 'OFFLINE';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->personId)
            ->willReturn($this->person);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'name' => $newName,
            'phone' => $newPhone,
            'vehicleId' => $newVehicleId,
            'currentLocation' => $newLocation,
            'metadata' => $newMetadata,
            'status' => $newStatus,
        ];
        $result = $this->service->updateDeliveryPerson($this->personId, $data);
        $this->assertSame($this->person, $result);
        $this->assertSame($newName, $result->getName());
        $this->assertSame($newPhone, $result->getPhone());
        $this->assertSame($newVehicleId, $result->getVehicleId());
        $this->assertSame($newLocation, $result->getCurrentLocation());
        $this->assertSame($newMetadata, $result->getMetadata());
        $this->assertSame($newStatus, $result->getStatus());
    }

    public function testUpdateDeliveryPersonValidationEmptyName(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->personId)
            ->willReturn($this->person);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        $this->service->updateDeliveryPerson($this->personId, ['name' => '']);
    }

    public function testUpdateDeliveryPersonValidationEmptyPhone(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->personId)
            ->willReturn($this->person);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone cannot be empty');
        $this->service->updateDeliveryPerson($this->personId, ['phone' => '   ']);
    }

    public function testUpdateDeliveryPersonNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->personId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateDeliveryPerson($this->personId, []);
    }

    public function testDeleteDeliveryPersonSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->personId)
            ->willReturn($this->person);
        $this->em->expects($this->once())->method('remove')->with($this->person);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteDeliveryPerson($this->personId);
    }

    public function testDeleteDeliveryPersonNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->personId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteDeliveryPerson($this->personId);
    }

    public function testFindByUserId(): void
    {
        $userId = 'user-123';
        $results = [$this->person];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($results);

        $result = $this->service->findByUserId($userId);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'ACTIVE';
        $results = [$this->person];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }

    public function testFindByVehicleId(): void
    {
        $vehicleId = 'vehicle-1';
        $results = [$this->person];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['vehicleId' => $vehicleId])
            ->willReturn($results);

        $result = $this->service->findByVehicleId($vehicleId);
        $this->assertSame($results, $result);
    }
}
