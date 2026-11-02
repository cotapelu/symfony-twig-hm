<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryStore;
use App\Repository\Php\DeliveryStoreRepository;
use App\Service\Php\DeliveryStoreService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryStoreServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DeliveryStoreRepository&MockObject $repository;
    private DeliveryStoreService $service;

    private string $storeId;
    private DeliveryStore $store;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryStoreRepository::class);
        $this->service = new DeliveryStoreService($this->em, $this->repository);

        $this->storeId = bin2hex(random_bytes(16));
        $this->store = new DeliveryStore(
            $this->storeId,
            'owner-1',
            'RESTAURANT',
            'Pizza Place'
        );
    }

    public function testCreateDeliveryStoreSuccess(): void
    {
        $ownerId = 'owner-123';
        $type = 'GROCERY';
        $name = 'Mini Mart';
        $license = 'LIC-456';
        $metadata = ['location' => 'downtown'];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryStore $s) use (&$captured, $ownerId, $type, $name, $license, $metadata) {
                $captured = $s;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createDeliveryStore($ownerId, $type, $name, $license, $metadata);
        $this->assertInstanceOf(DeliveryStore::class, $result);
        $this->assertSame($ownerId, $result->getOwnerId());
        $this->assertSame($type, $result->getType());
        $this->assertSame($name, $result->getName());
        $this->assertSame($license, $result->getLicense());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateDeliveryStoreValidationEmptyOwnerId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner ID is required');
        $this->service->createDeliveryStore('', 'type', 'name');
    }

    public function testCreateDeliveryStoreValidationEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type is required');
        $this->service->createDeliveryStore('owner', '', 'name');
    }

    public function testCreateDeliveryStoreValidationEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        $this->service->createDeliveryStore('owner', 'type', '   ');
    }

    public function testGetDeliveryStoreSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->storeId)
            ->willReturn($this->store);

        $result = $this->service->getDeliveryStore($this->storeId);
        $this->assertSame($this->store, $result);
    }

    public function testGetDeliveryStoreNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->storeId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DeliveryStore not found');
        $this->service->getDeliveryStore($this->storeId);
    }

    public function testUpdateDeliveryStoreSuccess(): void
    {
        $newOwnerId = 'owner-2';
        $newType = 'CAFE';
        $newName = 'Coffee Shop';
        $newLicense = 'LIC-999';
        $newMetadata = ['rating' => 4.5];
        $newStatus = 'ACTIVE';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->storeId)
            ->willReturn($this->store);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'ownerId' => $newOwnerId,
            'type' => $newType,
            'name' => $newName,
            'license' => $newLicense,
            'metadata' => $newMetadata,
            'status' => $newStatus,
        ];
        $result = $this->service->updateDeliveryStore($this->storeId, $data);
        $this->assertSame($this->store, $result);
        $this->assertSame($newOwnerId, $result->getOwnerId());
        $this->assertSame($newType, $result->getType());
        $this->assertSame($newName, $result->getName());
        $this->assertSame($newLicense, $result->getLicense());
        $this->assertSame($newMetadata, $result->getMetadata());
        $this->assertSame($newStatus, $result->getStatus());
    }

    public function testUpdateDeliveryStoreValidationEmptyOwnerId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->storeId)
            ->willReturn($this->store);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner ID cannot be empty');
        $this->service->updateDeliveryStore($this->storeId, ['ownerId' => '']);
    }

    public function testUpdateDeliveryStoreValidationEmptyType(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->storeId)
            ->willReturn($this->store);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type cannot be empty');
        $this->service->updateDeliveryStore($this->storeId, ['type' => '']);
    }

    public function testUpdateDeliveryStoreValidationEmptyName(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->storeId)
            ->willReturn($this->store);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        $this->service->updateDeliveryStore($this->storeId, ['name' => '   ']);
    }

    public function testUpdateDeliveryStoreNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->storeId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateDeliveryStore($this->storeId, []);
    }

    public function testDeleteDeliveryStoreSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->storeId)
            ->willReturn($this->store);
        $this->em->expects($this->once())->method('remove')->with($this->store);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteDeliveryStore($this->storeId);
    }

    public function testDeleteDeliveryStoreNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->storeId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteDeliveryStore($this->storeId);
    }

    public function testFindByOwnerId(): void
    {
        $ownerId = 'owner-123';
        $results = [$this->store];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['ownerId' => $ownerId])
            ->willReturn($results);

        $result = $this->service->findByOwnerId($ownerId);
        $this->assertSame($results, $result);
    }

    public function testFindByType(): void
    {
        $type = 'RESTAURANT';
        $results = [$this->store];
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
        $results = [$this->store];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }
}
