<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryVehicle;
use App\Repository\Php\DeliveryVehicleRepository;
use App\Service\Php\DeliveryVehicleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryVehicleServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DeliveryVehicleRepository&MockObject $repository;
    private DeliveryVehicleService $service;

    private string $vehicleId;
    private DeliveryVehicle $vehicle;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryVehicleRepository::class);
        $this->service = new DeliveryVehicleService($this->em, $this->repository);

        $this->vehicleId = bin2hex(random_bytes(16));
        $this->vehicle = new DeliveryVehicle(
            $this->vehicleId,
            'BIKE',
            'Honda',
            2
        );
    }

    public function testCreateVehicleSuccess(): void
    {
        $type = 'CAR';
        $model = 'Toyota';
        $capacity = 4;
        $plateNumber = 'XYZ-1234';
        $status = 'AVAILABLE';
        $metadata = ['color' => 'blue'];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryVehicle $v) use (&$captured, $type, $model, $capacity, $plateNumber, $status, $metadata) {
                $captured = $v;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createVehicle($type, $model, $capacity, $plateNumber, $status, $metadata);
        $this->assertInstanceOf(DeliveryVehicle::class, $result);
        $this->assertSame($type, $result->getType());
        $this->assertSame($model, $result->getModel());
        $this->assertSame($capacity, $result->getCapacity());
        $this->assertSame($plateNumber, $result->getPlateNumber());
        $this->assertSame($status, $result->getStatus());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateVehicleValidationEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle type is required');
        $this->service->createVehicle('', 'Model', 2);
    }

    public function testCreateVehicleValidationEmptyModel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle model is required');
        $this->service->createVehicle('type', '   ', 2);
    }

    public function testCreateVehicleValidationCapacityNotPositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Capacity must be positive');
        $this->service->createVehicle('type', 'model', 0);
    }

    public function testGetVehicleSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->vehicleId)
            ->willReturn($this->vehicle);

        $result = $this->service->getVehicle($this->vehicleId);
        $this->assertSame($this->vehicle, $result);
    }

    public function testGetVehicleNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->vehicleId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DeliveryVehicle not found');
        $this->service->getVehicle($this->vehicleId);
    }

    public function testUpdateVehicleSuccess(): void
    {
        $newType = 'VAN';
        $newModel = 'Ford';
        $newCapacity = 8;
        $newPlate = 'PLATE-999';
        $newStatus = 'IN_MAINTENANCE';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->vehicleId)
            ->willReturn($this->vehicle);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'type' => $newType,
            'model' => $newModel,
            'capacity' => $newCapacity,
            'plateNumber' => $newPlate,
            'status' => $newStatus,
        ];
        $result = $this->service->updateVehicle($this->vehicleId, $data);
        $this->assertSame($this->vehicle, $result);
        $this->assertSame($newType, $result->getType());
        $this->assertSame($newModel, $result->getModel());
        $this->assertSame($newCapacity, $result->getCapacity());
        $this->assertSame($newPlate, $result->getPlateNumber());
        $this->assertSame($newStatus, $result->getStatus());
    }

    public function testUpdateVehicleValidationEmptyType(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->vehicleId)
            ->willReturn($this->vehicle);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle type cannot be empty');
        $this->service->updateVehicle($this->vehicleId, ['type' => '']);
    }

    public function testUpdateVehicleValidationEmptyModel(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->vehicleId)
            ->willReturn($this->vehicle);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle model cannot be empty');
        $this->service->updateVehicle($this->vehicleId, ['model' => '   ']);
    }

    public function testUpdateVehicleValidationCapacityNotPositive(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->vehicleId)
            ->willReturn($this->vehicle);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Capacity must be positive');
        $this->service->updateVehicle($this->vehicleId, ['capacity' => -2]);
    }

    public function testUpdateVehicleNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->vehicleId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateVehicle($this->vehicleId, []);
    }

    public function testDeleteVehicleSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->vehicleId)
            ->willReturn($this->vehicle);
        $this->em->expects($this->once())->method('remove')->with($this->vehicle);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteVehicle($this->vehicleId);
    }

    public function testDeleteVehicleNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->vehicleId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteVehicle($this->vehicleId);
    }

    public function testFindByType(): void
    {
        $type = 'MOTORCYCLE';
        $results = [$this->vehicle];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type])
            ->willReturn($results);

        $result = $this->service->findByType($type);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'ACTIVE';
        $results = [$this->vehicle];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }
}
