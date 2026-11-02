<?php

declare(strict_types=1);

namespace App\Tests\Unit\Business;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\BusinessService;
use App\Entity\Php\Business;
use App\Repository\Php\BusinessRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class BusinessServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private BusinessRepository $repository;
    private BusinessService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(BusinessRepository::class);
        $this->service = new BusinessService($this->em, $this->repository);
    }

    public function testCreateBusiness_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createBusiness(
            'owner-123',
            'My Business',
            'RETAIL',
            '123 Main St',
            '0901234567',
            'test@example.com',
            'TAX-001',
            ['category' => 'electronics']
        );

        $this->assertInstanceOf(Business::class, $result);
        $this->assertEquals('owner-123', $result->getOwnerId());
        $this->assertEquals('My Business', $result->getName());
        $this->assertEquals('RETAIL', $result->getType());
        $this->assertEquals('ACTIVE', $result->getStatus());
    }

    public function testCreateBusiness_EmptyOwner_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createBusiness('', 'Name', 'TYPE');
    }

    public function testCreateBusiness_EmptyName_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createBusiness('owner', '', 'TYPE');
    }

    public function testCreateBusiness_EmptyType_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createBusiness('owner', 'Name', '');
    }

    public function testGetBusiness_Exists(): void
    {
        $business = new Business('id-1', 'owner-1', 'Biz', 'TYPE');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($business);
        $this->service->setCurrentUser('owner-1');
        $result = $this->service->getBusiness('id-1');
        $this->assertSame($business, $result);
    }

    public function testGetBusiness_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getBusiness('missing');
    }

    public function testUpdateBusiness_Success(): void
    {
        $business = new Business('id-1', 'owner-1', 'Old Name', 'OLD_TYPE');
        $this->repository->expects($this->once())->method('find')->willReturn($business);
        $this->em->expects($this->once())->method('flush');
        $this->service->setCurrentUser('owner-1');

        $result = $this->service->updateBusiness('id-1', [
            'name' => 'New Name',
            'type' => 'NEW_TYPE',
            'status' => 'INACTIVE',
        ]);

        $this->assertEquals('New Name', $result->getName());
        $this->assertEquals('NEW_TYPE', $result->getType());
        $this->assertEquals('INACTIVE', $result->getStatus());
    }

    public function testUpdateBusiness_InvalidEmail_ThrowsException(): void
    {
        $business = new Business('id-1', 'owner-1', 'Name', 'TYPE');
        $this->repository->expects($this->once())->method('find')->willReturn($business);
        $this->service->setCurrentUser('owner-1');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        $this->service->updateBusiness('id-1', ['email' => 'invalid-email']);
    }

    public function testUpdateBusiness_NotFound_ThrowsException(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateBusiness('missing', ['name' => 'New']);
    }

    public function testDeleteBusiness_Success(): void
    {
        $business = new Business('id-1', 'owner-1', 'Name', 'TYPE');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($business);
        $this->em->expects($this->once())->method('remove')->with($business);
        $this->em->expects($this->once())->method('flush');
        $this->service->setCurrentUser('owner-1');

        $this->service->deleteBusiness('id-1');
    }

    public function testFindByOwnerId_ReturnsArray(): void
    {
        $businesses = [new Business('id-1', 'owner-123', 'Biz', 'TYPE')];
        $this->repository->expects($this->once())->method('findBy')->with(['ownerId' => 'owner-123'])->willReturn($businesses);
        $result = $this->service->findByOwnerId('owner-123');
        $this->assertCount(1, $result);
    }

    public function testFindByType_ReturnsArray(): void
    {
        $businesses = [new Business('id-1', 'owner-1', 'Biz', 'RETAIL')];
        $this->repository->expects($this->once())->method('findBy')->with(['type' => 'RETAIL'])->willReturn($businesses);
        $result = $this->service->findByType('RETAIL');
        $this->assertCount(1, $result);
    }

    public function testFindByStatus_ReturnsArray(): void
    {
        $businesses = [new Business('id-1', 'owner-1', 'Biz', 'TYPE')];
        $this->repository->expects($this->once())->method('findBy')->with(['status' => 'ACTIVE'])->willReturn($businesses);
        $result = $this->service->findByStatus('ACTIVE');
        $this->assertCount(1, $result);
    }

    public function testUpdate_OnlyPhone(): void
    {
        $business = new Business('id-1', 'owner-1', 'Name', 'TYPE');
        $this->repository->expects($this->once())->method('find')->willReturn($business);
        $this->em->expects($this->once())->method('flush');
        $this->service->setCurrentUser('owner-1');

        $result = $this->service->updateBusiness('id-1', ['phone' => '0987654321']);

        $this->assertEquals('0987654321', $result->getPhone());
    }

    public function testUpdate_EmailWithValidFormat(): void
    {
        $business = new Business('id-1', 'owner-1', 'Name', 'TYPE');
        $this->repository->expects($this->once())->method('find')->willReturn($business);
        $this->em->expects($this->once())->method('flush');
        $this->service->setCurrentUser('owner-1');

        $result = $this->service->updateBusiness('id-1', ['email' => 'new@example.com']);

        $this->assertEquals('new@example.com', $result->getEmail());
    }
}
