<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\DeliveryStore;
use App\Service\Php\DeliveryStoreService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeliveryStoreServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private DeliveryStoreService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new DeliveryStoreService($this->em, $this->logger);
    }

    public function testCreateStoreSuccess(): void
    {
        $ownerId = 'user_owner';
        $name = 'Test Store';
        $address = '123 Test St';
        $phone = '0901234567';
        $category = 'grocery';
        $email = 'test@store.com';
        $description = 'A test store';
        $operatingHours = ['mon' => '9-17'];
        $metadata = ['lat' => 10.0, 'lon' => 106.0];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeliveryStore::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryStore $s) use (
                $ownerId, $name, $address, $phone, $category, $email, $description, $operatingHours, $metadata
            ) {
                return $s->getOwnerId() === $ownerId
                    && $s->getName() === $name
                    && $s->getAddress() === $address
                    && $s->getPhone() === $phone
                    && $s->getCategory() === $category
                    && $s->getEmail() === $email
                    && $s->getDescription() === $description
                    && $s->getOperatingHours() === $operatingHours
                    && $s->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Delivery store created', $this->anything());

        $result = $this->service->createStore(
            $ownerId, $name, $address, $phone, $category,
            $email, $description, $operatingHours, $metadata
        );
        $this->assertInstanceOf(DeliveryStore::class, $result);
        $this->assertSame($ownerId, $result->getOwnerId());
        $this->assertSame($name, $result->getName());
        $this->assertSame($category, $result->getCategory());
    }

    public function testGetStoreSuccess(): void
    {
        $id = 'store_123';
        $store = new DeliveryStore($id, 'owner', 'Store', 'Addr', 'phone', 'cat');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($store);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeliveryStore::class)
            ->willReturn($repo);

        $result = $this->service->getStore($id);
        $this->assertSame($store, $result);
    }

    public function testGetStoreByOwner(): void
    {
        $ownerId = 'owner_123';
        $store = new DeliveryStore('id', $ownerId, 'Store', 'Addr', 'phone', 'cat');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['ownerId' => $ownerId])
            ->willReturn($store);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getStoreByOwner($ownerId);
        $this->assertSame($store, $result);
    }

    public function testGetActiveStores(): void
    {
        $store1 = new DeliveryStore('id1', 'o1', 'S1', 'A1', 'p1', 'cat');
        $store2 = new DeliveryStore('id2', 'o2', 'S2', 'A2', 'p2', 'cat');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn([$store1, $store2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getActiveStores();
        $this->assertCount(2, $result);
    }

    public function testGetStoresByCategory(): void
    {
        $category = 'food';
        $store = new DeliveryStore('id', 'owner', 'Store', 'Addr', 'phone', $category);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category, 'status' => 'ACTIVE'])
            ->willReturn([$store]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getStoresByCategory($category);
        $this->assertCount(1, $result);
    }

    public function testSearchByName(): void
    {
        $query = 'test';
        $store1 = new DeliveryStore('id1', 'o1', 'Test Store', 'A1', 'p1', 'cat');
        $store2 = new DeliveryStore('id2', 'o2', 'Another Test', 'A2', 'p2', 'cat');

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('s.name LIKE :query')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('query', '%' . $query . '%')
            ->willReturnSelf();
        $query_obj = $this->createMock(\Doctrine\ORM\Query::class);
        $query_obj->expects($this->once())
            ->method('getResult')
            ->willReturn([$store1, $store2]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query_obj);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeliveryStore::class)
            ->willReturn($repo);

        $result = $this->service->searchByName($query);
        $this->assertCount(2, $result);
    }

    public function testUpdateStore(): void
    {
        $storeId = 'store_123';
        $store = new DeliveryStore($storeId, 'owner', 'Old Name', 'Addr', 'phone', 'cat');
        $newName = 'New Name';
        $newCategory = 'beverage';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($storeId)
            ->willReturn($store);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Delivery store updated', ['store_id' => $storeId]);

        $result = $this->service->updateStore($storeId, $newName, $newCategory);
        $this->assertSame($store, $result);
        $this->assertSame($newName, $result->getName());
        $this->assertSame($newCategory, $result->getCategory());
    }

    public function testUpdateStoreNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateStore('nonexistent', 'name', 'cat');
        $this->assertNull($result);
    }

    public function testDeleteStoreSuccess(): void
    {
        $storeId = 'store_123';
        $store = new DeliveryStore($storeId, 'owner', 'Store', 'Addr', 'phone', 'cat');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($storeId)
            ->willReturn($store);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($store);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteStore($storeId);
        $this->assertTrue($result);
    }

    public function testDeleteStoreNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deleteStore('nonexistent');
        $this->assertFalse($result);
    }
}
